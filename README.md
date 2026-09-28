# Knihovna-PHP
Webová aplikace pro evidenci knih. Aplikace obsahuje:
- Seřaditelný seznam knih (název knihy, jméno autora a rok vydání)
- Detail knihy pro dodatečné informace
- Možnost tisku seznamu knih.

V administrativní části:
- Přihlašování a odhlašování admina pomocí údajů ze .env souboru.
- Možnost zobrazit, tvořit a mazat záznamy knih.
- Import záznamů knih z JSON souborů.

## Spuštění projektu

1. Tvorba .env souboru z .env.example
2. Spuštění pomocí docker compose:
    
```sh
docker compose up -d --build
```

Pro spuštění PHPMyAdmin:

```sh
docker compose --profile tools up -d
```
