<?php

namespace App\Service;


class PlanifyService
{
    public function planifyLab(array $data): array
    {
        // On récupère les données
        $samples     = $data['samples'] ;
        $technicians = $data['technicians'] ;
        $equipments   = $data['equipment'] ;

        // On tri les échantillons selon leur priorité puis leur heure d'arrivée
        $samplesSorted = [];
        while (!empty($samples)) {
            $firstIndex = 0;
            $firstSample = null;

            foreach ($samples as $index => $sample) {

                // On donne une valeur aux différentes priorités pour pouvoir les comparer
                if ($sample['priority'] === 'STAT'){
                    $priority = 0;
                } elseif ($sample['priority'] === 'URGENT'){
                    $priority = 1;
                } else {
                    $priority = 2;
                }
                $arrivalTime = $this->timeToMinutes($sample['arrivalTime']); // Temps d'arrivée en minutes

                // On stocke le premier sample qui va être comparé avec les autres samples. Ce sample est considéré comme le "meilleur" pour l'instant
                if ($firstSample === null) {
                    $firstSample = $sample;
                    $firstIndex = $index;
                }

                // Dans le cas où le sample actuel n'est pas le "meilleur" sample, on récupère les valeurs de priorités et de temps d'arrivée au "meilleur" sample
                if ($firstSample['priority'] === 'STAT'){
                    $firstPriority = 0;
                } elseif ($firstSample['priority'] === 'URGENT'){
                    $firstPriority = 1;
                } else {
                    $firstPriority = 2;
                }
                $firstArrivalTime = $this->timeToMinutes($firstSample['arrivalTime']);

                // On compare le "meilleur" sample avec le sample actuel : on compare d'abord la priorité ou le temps d'arrivé en cas d'égalité et on en ressort le meilleur
                if ($priority < $firstPriority || ($priority == $firstPriority && $arrivalTime < $firstArrivalTime)) {
                    $firstSample = $sample;
                    $firstIndex = $index;
                }
            }

            // On ajoute le "meilleur" sample trouvé
            $samplesSorted[] = $firstSample;

            // On enlève le sample de la liste initiale
            unset($samples[$firstIndex]);
        }
        $samples = $samplesSorted;

        // On prépare la disponibilité des techniciens
        foreach ($technicians as $index => $technician) {
            $technicians[$index]['freeTime'] = $this->timeToMinutes($technician['startTime']);
        }

        // On prépare la disponibilité des équipements
        foreach ($equipments as $index => $equipment) {
            if ($equipment['available'] === true) {
                // Equipement disponible à 00:00 si available = true
                $equipments[$index]['freeTime'] = 0;
            } else {
                $equipments[$index]['freeTime'] = null;
            }
        }

        // On initialise les données utiles pour le JSON que l'on va envoyer
        $schedule = [];
        $totalAnalysisTime = 0;
        $firstAnalysisTime = null;
        $lastAnalysisEndTime = null;
        $conflicts = 0;

        // On assigne à chaque échantillon un technicien et un équipement
        foreach ($samples as $sample) {
            $sampleType = $sample['type'];
            $samplePriority = $sample['priority'];
            $sampleAnalysisTime = $sample['analysisTime'];
            $sampleArrivalTime = $this->timeToMinutes($sample['arrivalTime']);

            // On trouve un technicien
            $chosenTechId = null;
            $chosenTechStart = null;
            foreach ($technicians as $index => $technician) {
                if ($technician['speciality'] === $sampleType || $technician['speciality'] === 'GENERAL') {
                    $technicianEndTime = $this->timeToMinutes($technician['endTime']);

                    // Le technicien commence lorsqu'il est prêt et que l'échantillon est arrivé
                    $startTime = max($technician['freeTime'], $sampleArrivalTime);
                    $endTime = $startTime + $sampleAnalysisTime;

                    // Le technicien doit finir l'analyse avant la fin de sa journée
                    if ($endTime <= $technicianEndTime) {
                        $chosenTechId = $index;
                        $chosenTechStart = $startTime;
                        break;
                    }
                }
            }

            if ($chosenTechId === null) {
                $conflicts++;
                continue; // Pas de technicien trouvé
            }

            // On trouve un équipement
            $chosenEquipId = null;
            $chosenEquipStart = null;
            foreach ($equipments as $index => $equipment) {
                if ($equipment['type'] === $sampleType) {

                    $startTime = max($equipment['freeTime'], $sampleArrivalTime);

                    $chosenEquipId = $index;
                    $chosenEquipStart = $startTime;
                    break;
                }
            }

            if ($chosenEquipId === null) {
                $conflicts++;
                continue; // Pas d'équipement trouvé
            }

            // Le technicien commencera à travailler dès que lui et l'équipement seront disponibles
            $startTime = max($chosenEquipStart, $chosenTechStart);
            $endTime = $startTime + $sampleAnalysisTime;

            // On revérifie la contrainte de fin de journée du technicien
            if ($endTime > $this->timeToMinutes($technicians[$chosenTechId]['endTime'])) {
                $conflicts++;
                continue;
            }

            // On met à jour les disponibilités du technicien et de l'équipement
            $technicians[$chosenTechId]['freeTime'] = $endTime;
            $equipments[$chosenEquipId]['freeTime'] = $endTime;

            // On enregistre l'analyse dans le planning
            $schedule[] = [
                'sampleId' => $sample['id'],
                'technicianId' => $technicians[$chosenTechId]['id'],
                'equipmentId' => $equipments[$chosenEquipId]['id'],
                'startTime' => $this->minutesToTime($startTime),
                'endTime' => $this->minutesToTime($endTime),
                'priority' => $samplePriority,
            ];

            // Metriques

            // On ajoute le temps d'analyse de chaque échantillon traités
            $totalAnalysisTime += $sampleAnalysisTime;

            // On cherche l'heure de départ de la première analyse
            if ($firstAnalysisTime === null) {
                $firstAnalysisTime = $startTime;
            } else {
                $firstAnalysisTime = min($firstAnalysisTime, $startTime);
            }

            // On cherche l'heure de fin de la dernière analyse
            if ($lastAnalysisEndTime === null) {
                $lastAnalysisEndTime = $endTime;
            } else {
                $lastAnalysisEndTime = max($lastAnalysisEndTime, $endTime);
            }
        }

        // On calcule le temps total entre la première et la dernière analyse
        $totalTime = $lastAnalysisEndTime - $firstAnalysisTime;

        // On calcule l'efficacité des analyses
        $efficiency = ($totalTime / $totalAnalysisTime)*100 ;

        return [
            'schedule' => $schedule,
            'metrics'  => [
                'totalTime' => $totalTime,
                'efficiency'=> $efficiency,
                'conflicts' => $conflicts,
            ],
        ];
    }


    // Helpers

    // Transformer un temps 'hh:mm' en minutes
    private function timeToMinutes(string $time): int
    {
        $timeSplit = explode(':', $time);

        $hours = $timeSplit[0] ?? 0;
        $minutes = $timeSplit[1] ?? 0;

        return $hours * 60 + $minutes;
    }

    // Transformer un temps en minutes en 'hh:mm'
    private function minutesToTime(string $minutes): string
    {
        $heures = floor($minutes / 60);
        $minutesRestantes = $minutes % 60;

        return sprintf('%02d:%02d', $heures, $minutesRestantes);
    }
}
