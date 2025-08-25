# Planify Lab

C'est un projet Symfony permettant de planifier automatiquement l’analyse d’échantillons en fonction :
- des **priorités** (STAT > URGENT > ROUTINE),
- des **techniciens** (spécialistes ou généralistes, avec horaires de travail),
- des **équipements** disponibles (par type d’échantillon).

## Endpoint

### Post /planify
Prend en entrée un JSON décrivant les échantillons, techniciens et équipements et renvoie un planning complet avec des métriques.

**Exemple de JSON d'entrée :**

```json

{
  "samples": [
    {
      "id": "S001", 
      "type": "BLOOD",
      "priority": "URGENT",
      "analysisTime": 60,
      "arrivalTime": "09:00", 
      "patientId": "P001"
    },

    {
      "id": "S002",
      "type": "URINE", 
      "priority": "STAT",
      "analysisTime": 30,
      "arrivalTime": "09:15",
      "patientId": "P002" 
    },
    {
      "id": "S003",
      "type": "BLOOD",
      "priority": "ROUTINE", 
      "analysisTime": 45,
      "arrivalTime": "09:00",
      "patientId": "P003"
    },
    {
      "id": "S004",
      "type": "BLOOD", 
      "priority": "STAT",
      "analysisTime": 15,
      "arrivalTime": "09:30",
      "patientId": "P004" 
    },
    {
      "id": "S005",
      "type": "URINE", 
      "priority": "ROUTINE",
      "analysisTime": 20,
      "arrivalTime": "10:30",
      "patientId": "P005" 
    },
    {
      "id": "S006",
      "type": "TISSUE", 
      "priority": "URGENT",
      "analysisTime": 50,
      "arrivalTime": "15:00",
      "patientId": "P006" 
    },
    {
      "id": "S007",
      "type": "TISSUE", 
      "priority": "URGENT",
      "analysisTime": 35,
      "arrivalTime": "13:30",
      "patientId": "P007" 
    },
    {
      "id": "S008",
      "type": "BLOOD", 
      "priority": "STAT",
      "analysisTime": 40,
      "arrivalTime": "14:30",
      "patientId": "P008" 
    },
    {
      "id": "S009",
      "type": "URINE", 
      "priority": "STAT",
      "analysisTime": 10,
      "arrivalTime": "16:30",
      "patientId": "P009" 
    },
    {
      "id": "S0010",
      "type": "TISSUE", 
      "priority": "URGENT",
      "analysisTime": 30,
      "arrivalTime": "11:30",
      "patientId": "P0010" 
    }
  ],
  "technicians": [
    {
      "id": "T001",
      "speciality": "BLOOD",
      "startTime": "08:00", 
      "endTime": "17:00"
    },
    {
      "id": "T002", 
      "speciality": "GENERAL",
      "startTime": "09:00",
      "endTime": "18:00"
    },
    {
      "id": "T003", 
      "speciality": "URINE",
      "startTime": "08:30",
      "endTime": "17:30"
    },
    {
      "id": "T004", 
      "speciality": "TISSUE",
      "startTime": "08:00",
      "endTime": "17:00"
    }
  ],
  "equipment": [
    {
      "id": "E001",
      "name": "Analyseur Sang A",
      "type": "BLOOD", 
      "available": true
    },
      {
      "id": "E002", 
      "type": "URINE",
      "available": true
    },
      {
      "id": "E003", 
      "type": "TISSUE",
      "available": true
    },
      {
      "id": "E004", 
      "type": "BLOOD",
      "available": false
    }
  ]
}
```

## Tests avec Postman

Les tests de l'API ont été effectués avec **Postman**, en envoyant à l'adresse http://127.0.0.1:8000/planify une requête **POST** contenant le JSON présent ci-dessus.

Voici le résultat obtenu avec ce JSON en entrée :

```json

{
    "schedule": [
        {
            "sampleId": "S002",
            "technicianId": "T003",
            "equipmentId": "E002",
            "startTime": "09:15",
            "endTime": "09:45",
            "priority": "STAT"
        },
        {
            "sampleId": "S004",
            "technicianId": "T001",
            "equipmentId": "E001",
            "startTime": "09:30",
            "endTime": "09:45",
            "priority": "STAT"
        },
        {
            "sampleId": "S008",
            "technicianId": "T001",
            "equipmentId": "E001",
            "startTime": "14:30",
            "endTime": "15:10",
            "priority": "STAT"
        },
        {
            "sampleId": "S009",
            "technicianId": "T003",
            "equipmentId": "E002",
            "startTime": "16:30",
            "endTime": "16:40",
            "priority": "STAT"
        },
        {
            "sampleId": "S001",
            "technicianId": "T002",
            "equipmentId": "E001",
            "startTime": "15:10",
            "endTime": "16:10",
            "priority": "URGENT"
        },
        {
            "sampleId": "S0010",
            "technicianId": "T004",
            "equipmentId": "E003",
            "startTime": "11:30",
            "endTime": "12:00",
            "priority": "URGENT"
        },
        {
            "sampleId": "S007",
            "technicianId": "T004",
            "equipmentId": "E003",
            "startTime": "13:30",
            "endTime": "14:05",
            "priority": "URGENT"
        },
        {
            "sampleId": "S006",
            "technicianId": "T004",
            "equipmentId": "E003",
            "startTime": "15:00",
            "endTime": "15:50",
            "priority": "URGENT"
        },
        {
            "sampleId": "S003",
            "technicianId": "T001",
            "equipmentId": "E001",
            "startTime": "16:10",
            "endTime": "16:55",
            "priority": "ROUTINE"
        },
        {
            "sampleId": "S005",
            "technicianId": "T002",
            "equipmentId": "E002",
            "startTime": "16:40",
            "endTime": "17:00",
            "priority": "ROUTINE"
        }
    ],
    "metrics": {
        "totalTime": 465,
        "efficiency": 72.04301075268818,
        "conflicts": 0
    }
}
```

Je n'ai pas eu le temps de perfectionné le code, **il resterait notamment à faire :**
- Corriger un bug qui parfois priorise un technicien généraliste alors qu'un technicien spécialiste vient de finir une analyse
- Peaufiner avec des meilleures optimisations et de meilleurs tests et validations