<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dryRun = in_array('--dry-run', $argv);
echo "=== SpaCart -> Dolibarr Variant Migration ===\n";
echo "Mode: " . ($dryRun ? "DRY RUN" : "LIVE") . "\n\n";

$db = new mysqli('localhost', 'spacart_user', 'SpAcArT2026xCoex', 'erp_main');
if ($db->connect_error) die("Conn fail: " . $db->connect_error . "\n");
$db->set_charset('utf8mb4');
$stats = ['attr'=>0,'vals'=>0,'children'=>0,'combos'=>0,'c2v'=>0,'parents'=>0];

echo "--- Phase 1: Prerequisites ---\n";
$res = $db->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA='erp_main' AND TABLE_NAME IN ('option_groups','options','variants','variant_items')");
$tt = [];
while ($r = $res->fetch_assoc()) $tt[$r['TABLE_NAME']] = $r['TABLE_TYPE'];
foreach (['option_groups','options','variants','variant_items'] as $t) {
    if (!isset($tt[$t])) die("Missing: $t\n");
    if ($tt[$t]==='VIEW') die("$t is VIEW already\n");
    echo "  [OK] $t = {$tt[$t]}\n";
}
$ogC = $db->query("SELECT COUNT(*) c FROM option_groups")->fetch_assoc()['c'];
$opC = $db->query("SELECT COUNT(*) c FROM options")->fetch_assoc()['c'];
$vaC = $db->query("SELECT COUNT(*) c FROM variants")->fetch_assoc()['c'];
$viC = $db->query("SELECT COUNT(*) c FROM variant_items")->fetch_assoc()['c'];
echo "  Source: og=$ogC, opt=$opC, var=$vaC, vi=$viC\n";

echo "\n--- Phase 2: Attributes ---\n";
$res = $db->query("SELECT name, COUNT(*) cnt, GROUP_CONCAT(DISTINCT groupid ORDER BY groupid) gids FROM option_groups GROUP BY name ORDER BY name");
$ogMap = []; $pos = 0;
while ($row = $res->fetch_assoc()) {
    $pos += 10;
    $ref = 'SPACART-' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $row['name']));
    echo "  Attr: {$row['name']} ({$row['cnt']} groups)\n";
    if (!$dryRun) {
        $st = $db->prepare("INSERT INTO llx_product_attribute (ref,ref_ext,label,position,entity) VALUES (?,?,?,?,1)");
        $rx = 'spacart'; $st->bind_param('sssi',$ref,$rx,$row['name'],$pos); $st->execute();
        $nid = $db->insert_id; $st->close();
    } else $nid = 90000+$pos;
    $stats['attr']++;
    foreach (explode(',',$row['gids']) as $g) $ogMap[intval(trim($g))] = $nid;
}
echo "  Total: {$stats['attr']}\n";

echo "\n--- Phase 3: Values ---\n";
$optMap = []; $cv = []; $vp = 0;
$res = $db->query("SELECT og.name gn, o.optionid, o.groupid, o.name oname FROM options o JOIN option_groups og ON o.groupid=og.groupid ORDER BY og.name,o.name,o.optionid");
while ($row = $res->fetch_assoc()) {
    $aid = $ogMap[intval($row['groupid'])] ?? null;
    if (!$aid) continue;
    $key = $aid.'|'.$row['oname'];
    if (isset($cv[$key])) { $optMap[intval($row['optionid'])] = $cv[$key]; continue; }
    $vp++;
    $ref = 'SPACART-OPT-'.$row['optionid'];
    if (!$dryRun) {
        $st = $db->prepare("INSERT INTO llx_product_attribute_value (fk_product_attribute,ref,value,entity,position) VALUES (?,?,?,1,?)");
        $st->bind_param('issi',$aid,$ref,$row['oname'],$vp); $st->execute();
        $nv = $db->insert_id; $st->close();
    } else $nv = 80000+$vp;
    $cv[$key] = $nv; $optMap[intval($row['optionid'])] = $nv; $stats['vals']++;
}
echo "  Total: {$stats['vals']} (deduped from $opC)\n";

$o2g = [];
$res = $db->query("SELECT optionid,groupid FROM options");
while ($r = $res->fetch_assoc()) $o2g[intval($r['optionid'])] = intval($r['groupid']);

echo "\n--- Phase 4: Variants ---\n";
$pmap = [];
$res = $db->query("SELECT DISTINCT productid FROM variants ORDER BY productid");
while ($r = $res->fetch_assoc()) {
    $pid = intval($r['productid']);
    $ex = $db->query("SELECT rowid FROM llx_product WHERE rowid=$pid")->fetch_assoc();
    if ($ex) { $pmap[$pid] = $ex['rowid']; continue; }
    $s = $db->query("SELECT * FROM variants WHERE productid=$pid LIMIT 1")->fetch_assoc();
    $ref = 'SPACART-P'.$pid;
    $lbl = $s['title'] ? preg_replace('/\s*-\s*[A-Z]{2,5}$/', '', $s['title']) : "Product $pid";
    if (!$dryRun) {
        $st = $db->prepare("INSERT INTO llx_product (ref,label,description,price,weight,fk_product_type,tosell,tobuy,datec,entity) VALUES (?,?,'SpaCart parent',?,?,0,1,1,NOW(),1)");
        $pr = floatval($s['price']); $wt = floatval($s['weight']);
        $st->bind_param('ssdd',$ref,$lbl,$pr,$wt); $st->execute();
        $pmap[$pid] = $db->insert_id; $st->close();
        echo "  Parent: $ref (id={$pmap[$pid]})\n";
    } else { $pmap[$pid] = 70000+$pid; echo "  [DRY] Parent: $ref\n"; }
    $stats['parents']++;
}
$res = $db->query("SELECT DISTINCT productid FROM option_groups WHERE productid NOT IN (SELECT DISTINCT productid FROM variants)");
while ($r = $res->fetch_assoc()) { $p = intval($r['productid']); if (!isset($pmap[$p])) $pmap[$p] = $p; }

$vMap = [];
$res = $db->query("SELECT v.*, GROUP_CONCAT(vi.optionid ORDER BY vi.optionid) oids FROM variants v LEFT JOIN variant_items vi ON v.variantid=vi.variantid GROUP BY v.variantid ORDER BY v.productid,v.variantid");
while ($row = $res->fetch_assoc()) {
    $opid = intval($row['productid']); $vid = intval($row['variantid']);
    $oids = $row['oids'] ? array_map('intval', explode(',', $row['oids'])) : [];
    $parentId = $pmap[$opid] ?? null;
    if (!$parentId) { echo "  WARN: no parent vid=$vid\n"; continue; }
    $cref = $row['sku'] ?: ('SPACART-V'.$vid);
    $clbl = $row['title'];
    if (!empty($oids)) {
        $lr = $db->query("SELECT o.name FROM options o JOIN option_groups og ON o.groupid=og.groupid WHERE o.optionid IN (".implode(',',$oids).") ORDER BY og.orderby");
        $pp = []; while ($l = $lr->fetch_assoc()) $pp[] = $l['name'];
        if ($pp) $clbl .= ' (' . implode(' - ', $pp) . ')';
    }
    if (!$dryRun) {
        $ex = $db->query("SELECT rowid FROM llx_product WHERE ref='".$db->real_escape_string($cref)."'")->fetch_assoc();
        if ($ex) $cref .= '-V'.$vid;
        $st = $db->prepare("INSERT INTO llx_product (ref,label,description,price,weight,fk_product_type,tosell,tobuy,datec,entity,stock) VALUES (?,?,'SpaCart child',?,?,0,1,1,NOW(),1,?)");
        $pr = floatval($row['price']); $wt = floatval($row['weight']); $av = intval($row['avail']);
        $st->bind_param('ssddi',$cref,$clbl,$pr,$wt,$av); $st->execute();
        $cid = $db->insert_id; $st->close();
    } else $cid = 60000+$vid;
    $stats['children']++;
    if (!$dryRun) {
        $st = $db->prepare("INSERT INTO llx_product_attribute_combination (fk_product_parent,fk_product_child,variation_price,variation_price_percentage,variation_weight,variation_ref_ext,entity) VALUES (?,?,0,0,0,?,1)");
        $re = 'spacart-variant-'.$vid; $st->bind_param('iis',$parentId,$cid,$re); $st->execute();
        $comboId = $db->insert_id; $st->close();
    } else $comboId = 50000+$vid;
    $stats['combos']++;
    $vMap[$vid] = ['combo'=>$comboId,'child'=>$cid,'opid'=>$opid];
    foreach ($oids as $oid) {
        $avid = $optMap[$oid] ?? null; if (!$avid) continue;
        $gid = $o2g[$oid] ?? null; $aid = $gid ? ($ogMap[$gid] ?? null) : null;
        if (!$aid) continue;
        if (!$dryRun) {
            $st = $db->prepare("INSERT INTO llx_product_attribute_combination2val (fk_prod_combination,fk_prod_attr,fk_prod_attr_val) VALUES (?,?,?)");
            $st->bind_param('iii',$comboId,$aid,$avid); $st->execute(); $st->close();
        }
        $stats['c2v']++;
    }
}
echo "  Children:{$stats['children']} Combos:{$stats['combos']} C2V:{$stats['c2v']} Parents:{$stats['parents']}\n";

echo "\n--- Phase 5: Rename & VIEWs ---\n";
if (!$dryRun) {
    $bk = $db->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA='erp_main' AND TABLE_NAME='option_groups_native_backup'")->fetch_assoc()['c'];
    if ($bk > 0) die("Backup tables exist already!\n");
    $db->query("RENAME TABLE option_groups TO option_groups_native_backup"); echo "  Renamed option_groups\n";
    $db->query("RENAME TABLE options TO options_native_backup"); echo "  Renamed options\n";
    $db->query("RENAME TABLE variants TO variants_native_backup"); echo "  Renamed variants\n";
    $db->query("RENAME TABLE variant_items TO variant_items_native_backup"); echo "  Renamed variant_items\n";

    $db->query("DROP TABLE IF EXISTS _spacart_og_map");
    $db->query("CREATE TABLE _spacart_og_map (old_groupid INT PRIMARY KEY,new_attr_id INT,old_productid INT,old_name VARCHAR(255),old_fullname VARCHAR(255),old_orderby INT,old_type VARCHAR(1),old_view_type VARCHAR(1),old_enabled TINYINT,old_variant TINYINT,INDEX(new_attr_id),INDEX(old_productid)) ENGINE=InnoDB");
    $res = $db->query("SELECT * FROM option_groups_native_backup");
    while ($r = $res->fetch_assoc()) {
        $g = intval($r['groupid']); $na = $ogMap[$g] ?? 0;
        $st = $db->prepare("INSERT INTO _spacart_og_map VALUES (?,?,?,?,?,?,?,?,?,?)");
        $st->bind_param('iiissisiii',$g,$na,$r['productid'],$r['name'],$r['fullname'],$r['orderby'],$r['type'],$r['view_type'],$r['enabled'],$r['variant']);
        $st->execute(); $st->close();
    }

    $db->query("DROP TABLE IF EXISTS _spacart_opt_map");
    $db->query("CREATE TABLE _spacart_opt_map (old_optionid INT PRIMARY KEY,new_val_id INT,old_groupid INT,old_pm DECIMAL(12,2) DEFAULT 0,old_pmt CHAR(1),old_wm DECIMAL(12,2) DEFAULT 0,old_wmt CHAR(1),INDEX(new_val_id),INDEX(old_groupid)) ENGINE=InnoDB");
    $res = $db->query("SELECT * FROM options_native_backup");
    while ($r = $res->fetch_assoc()) {
        $o = intval($r['optionid']); $nv = $optMap[$o] ?? 0;
        $st = $db->prepare("INSERT INTO _spacart_opt_map VALUES (?,?,?,?,?,?,?)");
        $st->bind_param('iiidsds',$o,$nv,$r['groupid'],$r['price_modifier'],$r['price_modifier_type'],$r['weight_modifier'],$r['weight_modifier_type']);
        $st->execute(); $st->close();
    }

    $db->query("DROP TABLE IF EXISTS _spacart_var_map");
    $db->query("CREATE TABLE _spacart_var_map (old_variantid INT PRIMARY KEY,new_combination_id INT,new_child_product_id INT,old_productid INT,new_parent_productid INT,old_def TINYINT DEFAULT 0,INDEX(new_combination_id),INDEX(old_productid)) ENGINE=InnoDB");
    $res = $db->query("SELECT * FROM variants_native_backup");
    while ($r = $res->fetch_assoc()) {
        $ov = intval($r['variantid']);
        if (!isset($vMap[$ov])) continue;
        $i = $vMap[$ov]; $np = $pmap[intval($r['productid'])] ?? intval($r['productid']);
        $st = $db->prepare("INSERT INTO _spacart_var_map VALUES (?,?,?,?,?,?)");
        $st->bind_param('iiiiii',$ov,$i['combo'],$i['child'],$r['productid'],$np,$r['def']);
        $st->execute(); $st->close();
    }
    echo "  Maps created\n";

    $db->query("CREATE VIEW option_groups AS SELECT m.old_groupid AS groupid,m.old_productid AS productid,m.old_name AS name,m.old_fullname AS fullname,m.old_orderby AS orderby,m.old_type AS type,m.old_view_type AS view_type,m.old_enabled AS enabled,m.old_variant AS variant FROM _spacart_og_map m JOIN llx_product_attribute a ON m.new_attr_id=a.rowid");
    echo $db->error ? "  ERR og: {$db->error}\n" : "  [OK] VIEW option_groups\n";

    $db->query("CREATE VIEW options AS SELECT m.old_optionid AS optionid,m.old_groupid AS groupid,v.value AS name,v.position AS orderby,1 AS enabled,m.old_pm AS price_modifier,m.old_pmt AS price_modifier_type,m.old_wm AS weight_modifier,m.old_wmt AS weight_modifier_type FROM _spacart_opt_map m JOIN llx_product_attribute_value v ON m.new_val_id=v.rowid");
    echo $db->error ? "  ERR opt: {$db->error}\n" : "  [OK] VIEW options\n";

    $db->query("CREATE VIEW variants AS SELECT m.old_variantid AS variantid,m.old_productid AS productid,CAST(COALESCE(p.stock,0) AS SIGNED) AS avail,0 AS avail_block,COALESCE(p.price,0) AS price,COALESCE(p.weight,0) AS weight,p.ref AS sku,p.label AS title,'' AS qty_per_box,'' AS supplied_as,'' AS supplier_code,m.old_def AS def FROM _spacart_var_map m JOIN llx_product_attribute_combination c ON m.new_combination_id=c.rowid JOIN llx_product p ON c.fk_product_child=p.rowid");
    echo $db->error ? "  ERR var: {$db->error}\n" : "  [OK] VIEW variants\n";

    $db->query("CREATE VIEW variant_items AS SELECT vm.old_variantid AS variantid,om.old_optionid AS optionid FROM llx_product_attribute_combination2val cv JOIN _spacart_var_map vm ON vm.new_combination_id=cv.fk_prod_combination JOIN _spacart_opt_map om ON om.new_val_id=cv.fk_prod_attr_val AND om.old_groupid IN (SELECT old_groupid FROM _spacart_og_map WHERE new_attr_id=cv.fk_prod_attr)");
    echo $db->error ? "  ERR vi: {$db->error}\n" : "  [OK] VIEW variant_items\n";
} else {
    echo "  [DRY] Would rename 4 tables, create 3 maps, 4 VIEWs\n";
}

echo "\n--- Phase 6: Verify ---\n";
if (!$dryRun) {
    $no=$db->query("SELECT COUNT(*) c FROM option_groups")->fetch_assoc()['c'];
    $np=$db->query("SELECT COUNT(*) c FROM options")->fetch_assoc()['c'];
    $nv=$db->query("SELECT COUNT(*) c FROM variants")->fetch_assoc()['c'];
    $ni=$db->query("SELECT COUNT(*) c FROM variant_items")->fetch_assoc()['c'];
    echo "  og: $ogC->$no ".($ogC==$no?"[MATCH]":"[MISMATCH]")."\n";
    echo "  opt: $opC->$np ".($opC==$np?"[MATCH]":"[MISMATCH]")."\n";
    echo "  var: $vaC->$nv ".($vaC==$nv?"[MATCH]":"[MISMATCH]")."\n";
    echo "  vi: $viC->$ni ".($viC==$ni?"[MATCH]":"[MISMATCH]")."\n";
    $res = $db->query("SELECT TABLE_NAME,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA='erp_main' AND TABLE_NAME IN ('option_groups','options','variants','variant_items','option_groups_native_backup','options_native_backup','variants_native_backup','variant_items_native_backup') ORDER BY TABLE_NAME");
    echo "\n  Types:\n";
    while ($r = $res->fetch_assoc()) echo "    {$r['TABLE_NAME']}: {$r['TABLE_TYPE']}\n";
    echo "\n  Tests:\n";
    $tests = [
        "SELECT * FROM option_groups LIMIT 3",
        "SELECT * FROM options LIMIT 3",
        "SELECT * FROM variants LIMIT 3",
        "SELECT * FROM variant_items LIMIT 3",
        "SELECT g.groupid,g.view_type FROM option_groups g LEFT JOIN options o ON g.groupid=o.groupid AND o.enabled=1 WHERE o.enabled=1 GROUP BY g.groupid LIMIT 5",
        "SELECT variantid,COUNT(variantid) cnt FROM variant_items GROUP BY variantid LIMIT 5",
        "SELECT COUNT(DISTINCT g.groupid) FROM option_groups g,options o WHERE g.variant=1 AND g.enabled=1 AND g.groupid=o.groupid AND o.enabled=1",
    ];
    foreach ($tests as $sql) {
        $r = $db->query($sql);
        $s = substr($sql, 0, 72);
        echo "    $s " . ($r ? $r->num_rows." rows [OK]" : "ERR: {$db->error}") . "\n";
    }
} else echo "  [DRY] Skip\n";

echo "\n=== Summary ===\n";
foreach ($stats as $k=>$v) echo "  $k: $v\n";
echo $dryRun ? "\nDRY RUN done.\n" : "\nMIGRATION COMPLETE.\n";
$db->close();
