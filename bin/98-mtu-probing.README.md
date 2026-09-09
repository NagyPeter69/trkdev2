# bin/98-mtu-probing.conf

Host-level `sysctl` config, not consumed by the app itself. Deploy target:
`/etc/sysctl.d/98-mtu-probing.conf` on the Tracker VM, applied with
`sysctl -p /etc/sysctl.d/98-mtu-probing.conf` (or `sysctl --system`), and
kept across reboots automatically since anything under `/etc/sysctl.d/` is
read at boot.

## Why this exists

Investigated 2026-09-08/09: external recipients of large asset-pack
downloads (`client/filedownload.php`) saw a hard, consistent throughput
ceiling — roughly 20-30 Mbit/s regardless of the recipient's own
connection quality (confirmed across a home broadband line, mobile 5G, and
a clean business leased line), while the exact same VM saturates a LAN
transfer at ~800 Mbit/s and a since-retired predecessor VM, on the same
Sophos UTM + Proxmox host + IP, maxed out external connections fine.

Root cause, confirmed via `tracepath`: the real WAN path MTU is 1492
(PPPoE's 8-byte overhead), starting at the first hop past the office
gateway, while this box's own interface sits at the default 1500 with
`net.ipv4.tcp_mtu_probing` at its default of `0`. Classic ICMP-based Path
MTU Discovery depends on a "Fragmentation Needed" message finding its way
back here — evidently that was being dropped somewhere on real multi-hop
internet paths (common; plenty of networks block ICMP outright), so this
box had no fallback and kept sending full-size segments that silently died
right at the PPPoE hop. Whatever the connection managed to push through
was retransmission trickle, not a real steady-state rate — which is why it
was invariant to the recipient's own bandwidth.

`tcp_mtu_probing=1` enables Linux's PLPMTUD (RFC 4821): the kernel detects
a stalling connection itself and shrinks MSS at the transport layer,
without depending on ICMP at all. Verified live: a recipient on a clean
150/150 leased line went from a flat ~25 Mbit/s to "almost maxing out" the
line's *available* bandwidth (which itself was down to ~80-90 Mbit/s from
other office traffic at the time).

## If this box gets migrated/rebuilt/cloned again

This is exactly the kind of host-level tuning that's invisible to a
straight VM clone or a git-based app deployment, and easy to lose (this
whole investigation started because the predecessor VM apparently *had* it
set some other, undocumented way, and it wasn't carried over when this VM
took over its IP). Re-apply `98-mtu-probing.conf` to `/etc/sysctl.d/` on
any new box serving this app from behind the same PPPoE uplink, and
consider re-running the `tracepath -n <any external host>` check from
`bin/` (or ad hoc) first to confirm the discovered PMTU is still 1492
before assuming this fix still applies unchanged.
