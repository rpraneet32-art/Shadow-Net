# Environment Setup

## Target machine
Local machine, Docker Desktop. No cloud VM.

## Required software
- Docker Desktop (with Docker Compose v2 built in)
- Git (optional, for pulling reference Dockerfiles/images)
- A terminal (bash/zsh/PowerShell)
- Web browser (to verify DVWA loads)
- Wireshark (optional, GUI PCAP inspection — not required, tcpdump output is enough)

## Verify before starting
Run and confirm no errors:
```
docker --version
docker compose version
```

## Directory structure to create
```
shadownet/
├── docker-compose.yml
├── attacker/
│   └── Dockerfile
├── logs/
│   └── (attack timestamp logs go here)
└── captures/
    └── (tcpdump .pcap files go here)
```

## Network requirements
- One Docker bridge network, `internal: true` (no route to host internet)
- Fixed subnet: `10.10.0.0/24`
- All containers (db, dvwa, attacker) attach to this one network

## Images needed
- `mysql:5.7` or `mysql:8` (DVWA works with either, 5.7 is safer for compatibility)
- `vulnerables/web-dvwa` (official DVWA Docker image) — or build DVWA from source if that image is unavailable
- Base image for attacker container: `kalilinux/kali-rolling` (preferred, has security tools) or `debian:bookworm-slim` with tools installed manually

## Tools to install inside the attacker container (via Dockerfile)
- nmap
- hydra
- sqlmap
- tcpdump
- net-tools / iproute2 (for interface inspection)

## Port/access notes
- DVWA web UI should be reachable at `http://localhost:<mapped-port>` from the host browser only (for setup/verification) — this port mapping is for YOUR convenience, not part of the isolated attack network
- The `attacker` container should reach `dvwa` by container name/IP only, never via host-mapped port

## Not needed for this session (explicitly out of scope)
- CICFlowMeter — deferred to next session
- Cloud deployment
- Slowloris/hping3 DoS tooling — deferred to next session
- Any dashboard/backend/API — not part of these 3 blocks
