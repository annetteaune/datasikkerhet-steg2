# API-endepunkter for tilbakemeldingssystemet:

## Autentisering og Registrering:

- POST /api/api/index.php?endpoint=register&subresource=student

  - Formål: Registrere ny student
  - Data: fornavn, etternavn, epost, passord

- POST /api/api/index.php?endpoint=register&subresource=lecturer

  - Formål: Registrere ny foreleser med emne
  - Data: fornavn, etternavn, epost, passord, bilde, emne_navn, emne_kode, pin_kode

- POST /api/api/index.php?endpoint=login
  - Formål: Innlogging for både student og foreleser
  - Data: email, password

## Brukerhåndtering:

- GET /api/api/index.php?endpoint=student&id=X

  - Formål: Hente informasjon om en student
  - Parametre: id (student_id)

- GET /api/api/index.php?endpoint=lecturer&id=X
  - Formål: Hente informasjon om en foreleser
  - Parametre: id (foreleser_id)

## Meldingshåndtering:

- POST /api/api/index.php?endpoint=messages

  - Formål: Student sender melding til emne
  - Data: student_id, emne_id, innhold

- GET /api/api/index.php?endpoint=messages&emne_id=X&pin_kode=Y

  - Formål: Hente meldinger for et emne (krever PIN)
  - Parametre: emne_id, pin_kode

- POST /api/api/index.php?endpoint=response

  - Formål: Foreleser svarer på melding
  - Data: melding_id, foreleser_id, innhold

- POST /api/api/index.php?endpoint=comment

  - Formål: Gjestebruker legger til kommentar
  - Data: melding_id, innhold

- POST /api/api/index.php?endpoint=report
  - Formål: Rapportere upassende melding
  - Data: melding_id, grunn

## Emneadministrasjon:

- GET /api/api/index.php?endpoint=course&emne_id=X&pin_kode=Y

  - Formål: Hente detaljert informasjon om emne
  - Parametre: emne_id, pin_kode

- GET /api/api/index.php?endpoint=courses

  - Formål: Liste over alle tilgjengelige emner

- POST /api/api/index.php?endpoint=password&subresource=change
  - Formål: Endre passord for bruker
  - Data: user_id, old_password, new_password, user_type

## Eksempler på bruk:

### Liste alle emner:

```
GET /api/api/index.php?endpoint=courses
```

### Registrere student:

```
POST /api/api/index.php?endpoint=register&subresource=student
{
    "fornavn": "Ola",
    "etternavn": "Nordmann",
    "epost": "ola@example.com",
    "passord": "sikkertPassord123"
}
```

### Hente meldinger for et emne:

```
GET /api/api/index.php?endpoint=messages&emne_id=123&pin_kode=1234
```

### Sende ny melding:

```
POST /api/api/index.php?endpoint=messages
{
    "student_id": 1,
    "emne_id": 123,
    "innhold": "Dette er en testmelding"
}
```
