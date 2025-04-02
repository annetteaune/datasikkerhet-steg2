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

## bugs:

# Sikkerhetskrav:

- SEC-01 (Autentisering): Alle innlogginger skal benytte beskyttelse mot brute-force angrep.
- SEC-02 (Datavalidering): All data som sendes til databasen må håndteres med prepared statements.
  -- Utført: Alt (unntatt innlogging akkurat nå) bruker prepared statements. Jobber med saken, endres på når jeg implementerer ny hashing
- SEC-03 (Databeskyttelse): Passord skal lagres hash'et med en sterk algoritme.
- SEC-04 (Sessionshåndtering): Sessions må sikres med secure cookies og HTTP-only-flag.
- SEC-05 (Tilgangskontroll): Tydelig og robust tilgangskontroll basert på brukerroller.
  -- Utført: Vi har admin, student, foreleser og gjest som roller i databasen.
- SEC-06 (Registrering): Begrens antall kontoer som kan opprettes fra samme IP innen en bestemt tidsperiode.
- SEC-07 (Passordsikkerhet): Passord må bestå av minst 8 tegn, inkludert store bokstaver, tall og spesialtegn.
- SEC-08 (Nettsikkerhet): Implementer grunnleggende DDoS-beskyttelse gjennom rate limiting
