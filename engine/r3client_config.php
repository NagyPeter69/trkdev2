<?php
// Render node connection settings. R3 runs only on the dedicated render node
// (its licence is tied to that VM's kvm64 vCPU and SMBIOS UUID); this host
// never runs R3 itself. See SYSTEM_STATE.md's R3 section.
//
// 2026-10-21: the local-vs-remote switch (boot-time CPU detection writing
// /etc/trkdev-render-mode, R3_REMOTE_MODE) was removed together with the
// local R3 binaries - every call goes to the node, with the Ghostscript/
// DynaPDF fallback in r3client.php when it is down.

define('R3_REMOTE_URL', 'http://10.10.30.23/r3remote/run.php');
define('R3_REMOTE_TOKEN', getenv('TRKDEV_R3_TOKEN'));
