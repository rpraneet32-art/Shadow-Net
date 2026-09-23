# Block 1 — Digital Twin

**Time budget: ~40 minutes**

## Goal
Stand up an isolated, containerized DVWA + MySQL twin on its own Docker bridge network, reachable by other containers but not by the host internet.

## Steps

1. Create `docker-compose.yml` with:
   - A network named `twin-net`, driver `bridge`, `internal: true`, subnet `10.10.0.0/24`
   - Service `db`: image `mysql:5.7`, env vars for DVWA's expected DB name/user/password, attached to `twin-net` with a fixed IP (e.g. `10.10.0.11`)
   - Service `dvwa`: image `vulnerables/web-dvwa`, depends on `db`, attached to `twin-net` with a fixed IP (e.g. `10.10.0.10`), port mapped to host (e.g. `8080:80`) for browser verification only

2. Bring up the stack:
   ```
   docker compose up -d
   ```

3. Verify DVWA loads:
   - Open `http://localhost:8080` in browser
   - Complete DVWA's initial DB setup screen (click "Create / Reset Database")
   - Log in with default DVWA credentials (admin/password)

4. Verify network isolation:
   - From inside the `dvwa` container, confirm it can reach `db` (e.g. `ping 10.10.0.11` or check DB connection works)
   - Confirm the `twin-net` network has no route to the host's real internet (expected behavior of `internal: true` — do not attempt to `curl` an external site from inside these containers, it should fail)

5. Set DVWA security level to "low" (via DVWA's built-in Security setting) so scripted attacks in Block 2 behave predictably

6. (Optional, only if time remains) Add one extra intentional weakness on top of DVWA defaults — e.g. disable any login rate-limiting if present. Skip if time is tight.

## Deliverable
- `docker-compose.yml` committed/saved
- DVWA running and reachable at `localhost:8080`, DB connected, security level set to low
- Confirmed `dvwa` and `db` can reach each other on `twin-net`

## Explicitly not in scope for this block
- No custom-built web app (DVWA is the twin, as-is)
- No attacker container yet (that's Block 2)
- No traffic capture yet (that's Block 3)
