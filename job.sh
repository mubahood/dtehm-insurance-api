P=/usr/local/bin/php; H=/home/dtehxejt; D=$H/deploy_logs; cd $H/dip
SUM=$(md5sum < $D/release_c3f88f3.tar.gz | cut -d' ' -f1); [ "$SUM" = "" ] || { echo "CHECKSUM MISMATCH"; exit 1; }
EXIST=$(for f in $(cat $D/files2.txt); do [ -f "$f" ] && echo "$f"; done)
tar czf $D/pre_c3f88f3_files.tgz $EXIST bootstrap/cache/config.php bootstrap/cache/routes-v7.php && echo "backup ok"
tar xzf $D/release_c3f88f3.tar.gz && echo "extracted"
BAD=0; for f in $(cat $D/files2.txt); do case "$f" in *.php) $P -l "$f" >/dev/null 2>&1 || { echo "LINT FAIL $f"; BAD=1; };; esac; done
[ $BAD -ne 0 ] && { tar xzf $D/pre_c3f88f3_files.tgz; echo "RESTORED"; exit 1; }
echo "--- migrate"; $P artisan migrate --force 2>&1 | tail -3
echo "--- caches"; $P artisan config:cache 2>&1 | tail -1; $P artisan route:cache 2>&1 | tail -1; $P artisan view:clear 2>&1 | tail -1
echo "--- compile all views"; $P artisan view:cache 2>&1 | tail -2
echo "--- routes"; $P artisan route:list 2>&1 | grep -c "insurance-center"
echo "--- menu"; $P artisan tinker --execute='echo json_encode(DB::table("admin_menu")->where("uri","insurance-center")->first(["id","parent_id","title"]));' 2>&1
echo "--- center page renders (as first admin)"; $P artisan tinker --execute='$u=App\Models\User::find(DB::table("admin_role_users")->join("admin_roles","admin_roles.id","=","admin_role_users.role_id")->where("admin_roles.slug","admin")->value("user_id")); auth("admin")->login($u); $s=App\Models\InsuranceSubscription::where("status","Active")->get(); echo "policies: ".$s->count()."\n"; echo strlen(view("admin.insurance-center.policy",["policy"=>$s->first(),"s"=>$s->first()->summary(true),"deletedMonths"=>collect()])->render())." bytes policy view ok\n";' 2>&1 | tail -3
