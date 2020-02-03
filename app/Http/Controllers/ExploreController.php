<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExploreController extends Controller
{
    //
    public function getSnpInfo(Request $request){
    	$job_id = $request->job_id;

    	$show = $request->query('show') ?? 10;
    	$page = $request->query('page') ?? 1;
    	$query = $request->query('query') ?? null;


    	$chr_raw = DB::table('db_snp')->select('chrom')->where([['job_id', '=' ,$job_id],['user_id', '=',$request->user()->id]])->distinct()->get();
    	$chr = [];

    	foreach($chr_raw as $c){
    		$chr[] = $c->chrom;
    	}

    	$db_snp = DB::table('db_snp')->where([['job_id', '=' ,$job_id],['user_id', '=',$request->user()->id]]);

    	if($query != "{}"){
    		$query = json_decode($query, true);
    		$chrom = $pos = $ref = $alt = $rsid = $qual = $annotation = $impact ="";

    		$n = count($query['attr']);

    		// TO DO sanitize and do filtration input ... securing
    		for($i = 0; $i < $n; $i++){
    			if($query['attr'][$i] == 'chrom'){
                    if(!in_array($query['value'][$i], $chr)) continue;

    				if($chrom != "")
    					$chrom .= " OR ";
    				$chrom .= "{$query['attr'][$i]} {$query['operator'][$i]} '{$query['value'][$i]}'";

    			}else if($query['attr'][$i] == 'pos'){
    				$query['value'][$i] = (int)$query['value'][$i];
                    if($pos != "")
    					$pos .= " AND ";
    				$pos .= "{$query['attr'][$i]} {$query['operator'][$i]} '{$query['value'][$i]}'";

    			}else if($query['attr'][$i] == 'ref'){
                    if(!in_array($query['value'][$i], ['C', 'A', 'T', 'G'])) continue;

    				if($ref != "")
    					$ref .= " OR ";
    				$ref .= "{$query['attr'][$i]} {$query['operator'][$i]} '{$query['value'][$i]}'";

    			}else if($query['attr'][$i] == 'alt'){
                    if(!in_array($query['value'][$i], ['C', 'A', 'T', 'G'])) continue;

    				if($alt != "")
    					$alt .= " OR ";
    				$alt .= "{$query['attr'][$i]} {$query['operator'][$i]} '{$query['value'][$i]}'";

    			}else if($query['attr'][$i] == 'rs_id'){
    				if($rsid != "")
    					$rsid .= " OR ";
    				$rsid .= "{$query['attr'][$i]} LIKE '%{$query['value'][$i]}%'"; // not secure

    			}else if($query['attr'][$i] == 'qual'){
                    $query['value'][$i] = (int)$query['value'][$i];
    				if($qual != "")
    					$qual .= " OR ";
    				$qual .= "{$query['attr'][$i]} {$query['operator'][$i]} '{$query['value'][$i]}'";

    			}else if($query['attr'][$i] == 'eff_annotation'){
                    $query['attr'][$i] = "annotation";
                    if($annotation != "")
                        $annotation .= " OR ";
                    $annotation .= "{$query['attr'][$i]} LIKE '%{$query['value'][$i]}%'"; // not secure

                }else if($query['attr'][$i] == 'eff_impact'){
                    $query['attr'][$i] = "impact";
                    if($impact != "")
                        $impact .= " OR ";
                    $impact .= "{$query['attr'][$i]} LIKE '%{$query['value'][$i]}%'"; // not secure

                }
    		}

    		if($chrom)
    			$db_snp->whereRaw("($chrom)");
    		if($pos)
    			$db_snp->whereRaw("($pos)");
    		if($ref)
    			$db_snp->whereRaw("($ref)");
    		if($alt)
    			$db_snp->whereRaw("($alt)");
    		if($rsid)
    			$db_snp->whereRaw("($rsid)");
    		if($qual)
    			$db_snp->whereRaw("($qual)");
            if($annotation)
                $db_snp->whereRaw("($annotation)");
            if($impact)
                $db_snp->whereRaw("($impact)");

    		// dd($db_snp->toSql());
    	}


    	$offset = ($page-1)*$show;

    	$total = $db_snp->count();
    	$result = $db_snp->offset($offset)->limit($show)->get();
    

    	return ['pagination' => ['total' => (int)$total, 'per_page' => (int)$show, 'current_page' => (int)$page, 'last_page' => ceil($total/$show)], 'chrom' => $chr, 'result' => $result];
    }

    public function getSnpDetail(Request $request){
        $id = $request->id;

        $db_snp = DB::table('db_snp')->where([['user_id', '=', $request->user()->id],['id', '=', $id]])->first();

        if(!$db_snp){
            abort(404, 'Not found or not yours');
        }

        /*
            Parse FORMAT
        */
        $tmp = explode('_', $db_snp->format);
        $tmp2 = [];
        foreach($tmp as $t){
            $tmp2[] = explode(':', $t);
        }

        $db_snp->format = [];
        for($i = 0; $i < count($tmp2[0]); $i++){
            $db_snp->format[config('app.vcfInfo')[$tmp2[0][$i]]] = $tmp2[1][$i];
        }
        
        /*
            Parse INFO
        */
        $tmp = explode(';', $db_snp->info);
        $db_snp->info = [];
        foreach($tmp as $t){
            $tmp2 = explode('=', $t);
            $db_snp->info[config('app.vcfInfo')[$tmp2[0]]] = $tmp2[1];
        }

        return ['result' => $db_snp];
    }
}
