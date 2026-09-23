# Block 3 — Capture & Labeling Pipeline

**Time budget: ~50-60 minutes**
**Depends on: Block 1 and Block 2 (or run concurrently with Block 2's attacks, see note below)**

## Goal
Capture raw network traffic for the whole attack session as a PCAP, and produce a minimal text-based label log mapping time ranges to attack type. Full flow-level labeling (CICFlowMeter) is explicitly deferred to next session.

## Steps

1. Start `tcpdump` inside the `attacker` container (or on the `twin-net` bridge interface from the host, if easier) **before** running Block 2's attacks:
   ```
   tcpdump -i any -w /root/attacks/captures/session1.pcap
   ```
   - Run this in the background or a separate terminal/session so it keeps capturing while Block 2's attacks execute
   - If Block 2 has already been completed before this block starts, re-run the three attacks from Block 2 with tcpdump active this time, OR treat this as capturing traffic for a fresh short verification pass over the same three attacks

2. Let tcpdump run for the full duration of all three attacks (Nmap, Hydra, sqlmap), then stop it (Ctrl+C or `kill`).

3. Copy the resulting `.pcap` file to the mounted `captures/` volume so it's accessible outside the container.

4. Verify the PCAP is non-empty and contains traffic from all three attacks:
   ```
   tcpdump -r captures/session1.pcap -c 20
   ```
   Confirm you see traffic involving `10.10.0.20` (attacker) and `10.10.0.10` (dvwa).

5. Build the minimal label log — a plain text/CSV file (`logs/labels.csv`) with rows like:
   ```
   attack_type,start_time,end_time
   nmap_scan,<start>,<end>
   hydra_bruteforce,<start>,<end>
   sqlmap_sqli,<start>,<end>
   ```
   Populate `start_time`/`end_time` directly from Block 2's `logs/attack_log.txt`.

## Explicitly cut from this session (defer to next session)
- **CICFlowMeter** — do not attempt to install or run it today. It is Java-based and prone to dependency/version issues; setting it up eats time better spent finishing the capture. Next session: install CICFlowMeter (or fallback to Python's `NFStream` if CICFlowMeter setup fails), convert `session1.pcap` into labeled flow-level CSV features using `logs/labels.csv` as the ground truth for labeling by timestamp range.
- No ML training in this session — that depends on the flow-level CSV from next session's CICFlowMeter step.

## Deliverable
- `captures/session1.pcap` — non-empty, contains traffic from all three attacks
- `logs/labels.csv` — attack type + time range for each of the three attacks
- Confirmation these two files together are enough to reconstruct "what attack happened when" for next session's flow-labeling step

## End-of-session checkpoint (all 3 blocks)
- Twin running (DVWA + MySQL, isolated network)
- Three attacks executed and logged with timestamps
- One PCAP capturing the full session
- A label log ready to drive flow-level labeling next session
