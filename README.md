# DatasikkerhetGruppeProsjekt2025

## funker:

- student kan registreres
- foreleser kan registreres
- student kan logge inn
- student kan sende melding
- foreleser kan logge inn
- foreleser kan svare på melding
- gjest kan se emneside med PIN
- kan kommentere på melding
- kan rapportere melding
- endre passord
- glemt passord
- api

## funker ikke:

# Sikkerhetskrav:

- UTFØRT SEC-01 (Autentisering): Alle innlogginger skal benytte beskyttelse mot brute-force angrep.
- UTFØRT SEC-02 (Datavalidering): All data som sendes til databasen må håndteres med prepared statements.
- UTFØRT SEC-03 (Databeskyttelse): Passord skal lagres hash'et med en sterk algoritme.
- UTFØRT SEC-04 (Sessionshåndtering): Sessions må sikres med secure cookies og HTTP-only-flag.
- UTFØRT SEC-05 (Tilgangskontroll): Tydelig og robust tilgangskontroll basert på brukerroller.
- UTFØRT SEC-06 (Registrering): Begrens antall kontoer som kan opprettes fra samme IP innen en bestemt tidsperiode.
- UTFØRT SEC-07 (Passordsikkerhet): Passord må bestå av minst 8 tegn, inkludert store bokstaver, tall og spesialtegn.
- UTFØRT SEC-08 (Nettsikkerhet): Implementer grunnleggende DDoS-beskyttelse gjennom rate limiting

## bugs:

## TODO

- Rydd i kode
- Implementere graylog
