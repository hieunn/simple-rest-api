## Coding Challenge

### Installation
``` bash
# run following command
$ composer install

# If .env doesn't exist
$ cp .env.example .env
modify DB_* 

#Run migration
$php scripts/MigrateScript.php
```

### API endpoints:
#### Create routes with a hierarchy 
(POST /routes)
``` json

{
    "ROUTE 1": "ROUTE 3",
    "ROUTE 2": "ROUTE 3",
    "ROUTE 3": "ROUTE 4",
    "ROUTE 4": "ROUTE 5",
}
```

#### Return all routes with hierarchy 
(GET /routes)
``` json
{
    "ROUTE 5": {
        "ROUTE 4": {
            "ROUTE 3": {
                "ROUTE 1": [],
                "ROUTE 2": []
            }
        }
    }
}
```

#### Return specify routes by name and hierarchy level 
(GET /routes)
Request
name:ROUTE 3
level: 2
``` json
{
    "ROUTE 3": {
        "ROUTE 1": []
    }
}
```
