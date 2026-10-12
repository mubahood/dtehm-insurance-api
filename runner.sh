#!/bin/bash
# Temporary deployment runner (removed after the deploy). Runs ~/deploy_queue.sh once if present.
H=/home/dtehxejt
[ -f $H/deploy_queue.sh ] || exit 0
mkdir -p $H/deploy_logs
TS=$(date +%Y%m%d_%H%M%S)
mv $H/deploy_queue.sh $H/deploy_logs/job_$TS.sh
cd $H/dip
{ echo "=== START $TS"; bash $H/deploy_logs/job_$TS.sh; echo "=== EXIT $?"; } > $H/deploy_logs/job_$TS.log 2>&1
cp $H/deploy_logs/job_$TS.log $H/deploy_logs/latest.log
