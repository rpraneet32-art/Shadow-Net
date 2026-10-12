# ShadowNet

ShadowNet is a digital twin-based cybersecurity platform designed for attack simulation, network traffic capture, and automated ground-truth dataset generation. It provides an air-gapped, containerized environment to safely simulate adversarial tactics and validate defensive controls without exposing production systems to outages or data loss.

---

## Architecture Overview

The platform deploys three containerized services across a private, isolated Docker bridge network (`twin-net`, subnet `10.10.0.0/24`). The network is configured with `internal: true`, which disables default gateway routing and guarantees that no traffic can enter or leave the simulated environment.

```
+-------------------------------------------------------------------------+
|                  Docker Bridge: twin-net (10.10.0.0/24)                 |
|                        internal: true (Air-Gapped)                      |
|                                                                         |
|   +--------------------+               +--------------------+           |
|   |    shadownet-db    |               |   shadownet-dvwa   |           |
|   |    10.10.0.11      |<------------->|   10.10.0.10       |           |
|   |    MySQL 5.7       |   Port 3306   |   DVWA Web (Port 80)           |
|   +--------------------+               +---------^----------+           |
|             |                                    |                      |
|      (init.sql / volume)                         | Target               |
|                                                  |                      |
|                                        +---------+----------+           |
|                                        | shadownet-attacker |           |
|                                        |   10.10.0.20       |           |
|                                        |   Debian Bookworm  |           |
|                                        +--------------------+           |
+-------------------------------------------------------------------------+
```

### Components

1. **Digital Twin Web Service (`shadownet-dvwa`, IP: `10.10.0.10`)**
   - Vulnerable web application running on Apache 2.4 and PHP 7.0.
   - Configured with `default_security_level = low` to provide deterministic vulnerability targets for testing.
   - Dynamically loads credentials from the environment file via `config.inc.php`.

2. **Digital Twin Database Service (`shadownet-db`, IP: `10.10.0.11`)**
   - MySQL 5.7 relational database.
   - Automatically seeded on first startup using `init.sql`, populating the `dvwa` schema and benchmark user accounts.
   - Persistent storage backed by a Docker named volume (`db-data`).

3. **Attack Simulation Service (`shadownet-attacker`, IP: `10.10.0.20`)**
   - Debian Bookworm environment equipped with security tooling: `nmap`, `hydra`, `sqlmap`, `tcpdump`, `curl`, and `python3`.
   - Granted `NET_ADMIN` and `NET_RAW` Linux capabilities to allow packet sniffing and raw socket operations.
   - Executes scripted attack scenarios and logs ISO-8601 UTC execution timestamps.

---

## Directory Structure

```
Shadow-Net/
├── README.md                           # Project documentation
├── .env.example                        # Configuration template
├── .gitattributes                      # Line-ending normalization rules
├── .gitignore                          # Git exclusion rules
└── shadownet/
    ├── docker-compose.yml              # Multi-container orchestration spec
    ├── config.inc.php                  # DVWA runtime configuration loader
    ├── init.sql                        # Database schema and initial seed data
    ├── run_pipeline.sh                 # End-to-end capture and attack runner
    ├── attacker/
    │   ├── Dockerfile                  # Attacker image definition
    │   ├── passwords.txt               # Password dictionary for brute-force tests
    │   └── run_attacks.sh              # Attack execution and logging script
    ├── captures/
    │   └── session1.pcap               # Generated raw network traffic captures
    ├── logs/
    │   ├── attack_log.txt              # Raw attack start and end timestamps
    │   ├── labels.csv                  # Ground-truth attack metadata dataset
    │   ├── hydra_results.txt           # Hydra credential findings
    │   └── nmap_results.txt            # Nmap port and service scan results
    └── scripts/
        ├── generate_labels.py          # Log parser producing labels.csv
        └── verify_pcap.sh              # PCAP integrity and packet count validator
```

---

## Prerequisites

- **Docker Engine:** Version 20.10 or higher (or Docker Desktop 4.x on Windows/macOS).
- **Docker Compose:** Version 2.0 or higher.
- **Git**

---

## Setup and Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/rpraneet32-art/Shadow-Net.git
   cd Shadow-Net/shadownet
   ```

2. **Configure environment variables:**
   Copy the example environment file:
   ```bash
   cp .env.example .env
   ```
   The `.env` file serves as the Single Source of Truth for database credentials, container IPs, and security levels.

3. **Build and start the platform:**
   ```bash
   docker compose up -d --build
   ```

4. **Verify container status:**
   ```bash
   docker compose ps
   ```
   All three containers (`shadownet-db`, `shadownet-dvwa`, `shadownet-attacker`) should report an `Up` status.

---

## Running the Simulation Pipeline

### Automated End-to-End Pipeline

To execute packet capture, run all attack vectors, flush network buffers, and generate ground-truth labels in a single sequence:

```bash
docker exec -it shadownet-attacker /root/attacks/run_pipeline.sh
```

The pipeline executes four sequential stages:
1. **Packet Capture:** Starts background `tcpdump` listening on `eth0` with full snapshot length (`-s 0`) and immediate buffer flushing (`-U`), writing to `/captures/session1.pcap`.
2. **Attack Execution:** 
   - Obtains a valid DVWA session and CSRF token.
   - Runs Nmap TCP connect and service scan (`nmap -sV -sT -p 1-1000`).
   - Runs Hydra HTTP form brute-force against `/vulnerabilities/brute/`.
   - Runs sqlmap automated SQL injection against `/vulnerabilities/sqli/`.
   - Records UTC `START` and `END` timestamps for each attack phase to `/logs/attack_log.txt`.
3. **Capture Termination:** Sends `SIGINT` (`kill -2`) to `tcpdump` to cleanly finalize packet records and write the PCAP file trailer.
4. **Label Generation & Validation:** Parses the timestamp log to generate `/logs/labels.csv` and runs `/root/attacks/scripts/verify_pcap.sh` to confirm packet volume and target interaction.

### Running Individual Attacks Manually

You can also run specific attack functions on demand:

```bash
# Reconnaissance only (Nmap scan)
docker exec -it shadownet-attacker /root/attacks/run_attacks.sh nmap

# Credential brute-force only (Hydra)
docker exec -it shadownet-attacker /root/attacks/run_attacks.sh hydra

# SQL injection only (sqlmap)
docker exec -it shadownet-attacker /root/attacks/run_attacks.sh sqlmap
```

---

## Output Datasets and Deliverables

All outputs are saved to directories mounted on the host machine (`shadownet/logs` and `shadownet/captures`):

### 1. Raw Network Traffic (`shadownet/captures/session1.pcap`)
- Standard Ethernet II (`EN10MB`) capture format.
- Contains un-truncated application payloads (HTTP requests, login tokens, SQL injection strings) and transport handshakes.
- Ready for feature extraction using tools such as CICFlowMeter or NFStream.

### 2. Execution Log (`shadownet/logs/attack_log.txt`)
Contains standard ISO-8601 UTC timestamp boundaries for each attack:
```text
START nmap_scan 2026-10-07T18:15:33Z
END nmap_scan 2026-10-07T18:15:40Z
START hydra_bruteforce 2026-10-07T18:15:46Z
END hydra_bruteforce 2026-10-07T18:15:47Z
START sqlmap_sqli 2026-10-07T18:15:52Z
END sqlmap_sqli 2026-10-07T18:15:54Z
```

### 3. Ground-Truth Labels (`shadownet/logs/labels.csv`)
Correlates time intervals with attack metadata to enable flow-level labeling for machine learning training:
```csv
attack_type,category,start_time,end_time,source_ip,target_ip,target_port,protocol
nmap_scan,Reconnaissance,2026-10-07T18:15:33Z,2026-10-07T18:15:40Z,10.10.0.20,10.10.0.10,1-1000,TCP
hydra_bruteforce,Credential_Access,2026-10-07T18:15:46Z,2026-10-07T18:15:47Z,10.10.0.20,10.10.0.10,80,TCP/HTTP
sqlmap_sqli,Exploitation,2026-10-07T18:15:52Z,2026-10-07T18:15:54Z,10.10.0.20,10.10.0.10,80,TCP/HTTP
```

---

## Host Browser Verification (Optional)

Because `twin-net` enforces total network isolation (`internal: true`), published host ports are disabled by design. If you need to visually inspect the DVWA interface in your host browser for verification:

1. **Start a temporary proxy container:**
   ```bash
   docker run --rm -d --name shadownet-proxy -p 8080:80 alpine/socat tcp-listen:80,fork tcp-connect:10.10.0.10:80
   docker network connect shadownet_twin-net shadownet-proxy
   ```

2. **Access DVWA in your browser:**
   Navigate to `http://localhost:8080` (Default credentials: `admin` / `password`).

3. **Tear down the proxy when finished:**
   ```bash
   docker stop shadownet-proxy
   ```
   Stopping the proxy restores complete air-gap isolation without leaving open ports.

---

## Teardown and Cleanup

To stop and remove containers and networks:
```bash
docker compose down
```

To perform a complete reset, including deleting persistent database volumes:
```bash
docker compose down -v
```
