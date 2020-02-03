<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StoreDbSnp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:store-db {job_id} {refs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store identified SNP to database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $job_id = $this->argument('job_id');
        $refseq = $this->argument('refs');
        $user_id = \App\Job::findOrFail($job_id)->user_id;
   
        $vcf_file = config('app.jobsDir')."/$job_id/6_annotation/output.eff.vcf";
        // $vcf_file = config('app.jobsDir')."/$job_id/5_filtering/output.filtered.vcf";
        $vcf_handle = fopen($vcf_file, "r");
        $vcf_data = [];

        // $refseq = config('app.sequenceDir')."/references/".$refs;

        $vcfprimers = config('app.toolsDir.vcflib')."/vcfprimers";
        $flank_file = config('app.jobsDir')."/$job_id/7_storing_to_db/output.flank.fa";
        $flank_length = 100;

        $command = "$vcfprimers -f '{$refseq}' -l {$flank_length} '{$vcf_file}' > '{$flank_file}'";
        exec($command);

        $flank_handle = fopen($flank_file, "r");
        $submitted_count = 0;
        while(!feof($vcf_handle)){
            $line = fgets($vcf_handle, 4096);

            $vcf_data = explode("\t", $line);

            if (empty($vcf_data[0][0]) || $vcf_data[0][0] == '#') continue;

            /*
                $flank_xxx['info'] : >chr_pos_[LEFT|RIGHT]
            */
            $flank_left = [
                'info' => explode('_', trim(fgets($flank_handle, 4096))),
                'sequences' => trim(fgets($flank_handle, 4096)),
            ];

            $flank_right  = [
                'info' => explode('_', trim(fgets($flank_handle, 4096))),
                'sequences' => trim(fgets($flank_handle, 4096)),
            ];
            // check wether chromosome and position in $flank_xxx is the same as $vcf_data
            // if(substr($flank_left['info'][0],1) !== $vcf_data[0] || $flank_left['info'][1] !== $vcf_data[1]){
            //     $flank_left['sequences'] = "";
            // }
            // if(substr($flank_right['info'][0],1) !== $vcf_data[0] || $flank_right['info'][1] !== $vcf_data[1]){
            //     $flank_right['sequences'] = "";
            // }

            $tmp = explode(";",$vcf_data[7]);
            $c = 0;    
            do {
                $eff_loc = count($tmp)-1;
                $eff = $tmp[$eff_loc];
                $c++;
                array_pop($tmp);
            }while(strpos($tmp[$eff_loc-1],'ANN') !== false);
            if($c > 1)
                echo "I loop for $c times in position $vcf_data[1]\n";
            $vcf_data[7] = implode(";", $tmp);

            // proses eff
            $eff = substr($eff, 4); // remove ANN=
            $eff = explode(",", $eff); // explode for each effect
            $eff_count = count($eff);

            // $eff_detail = [
            //     'annotation' => '',
            //     'impact' => '',
            //     'gene_name' => '',
            //     'gene_id' => '',
            //     'feature_type' => '',
            //     'feature_id' => '',
            //     'transcript_biotype' => '',
            // ];
            $eff_detail = [];
            $tmp = [];
            for($i = 0;$i < $eff_count; $i++){
                $tmp[] = explode("|", $eff[$i]);
            }

            for($i = 1; $i <= 7; $i++){ // hanya mengambil annotation, impact, gene name, gene id, feature type, feature id, dan transcript biotype
                $eff_detail[$i-1] = [];
                for($j = 0; $j < count($tmp); $j++){
                    $eff_detail[$i-1][] = $tmp[$j][$i];
                }
            }
  
            DB::table('db_snp')->insert([
                [
                    'user_id' => $user_id,
                    'job_id' => $job_id,
                    'chrom' => $vcf_data[0],
                    'pos' => $vcf_data[1],
                    'rs_id' => $vcf_data[2],
                    'ref' => $vcf_data[3],
                    'alt' => $vcf_data[4],
                    'qual' => $vcf_data[5],
                    'filter' => $vcf_data[6],
                    'info' => $vcf_data[7],
                    'format' => $vcf_data[8]."_".trim($vcf_data[9]),
                    'annotation' => implode("|", $eff_detail[0]),
                    'impact' => implode("|", $eff_detail[1]),
                    'gene_name' => implode("|", $eff_detail[2]),
                    'gene_id' => implode("|", $eff_detail[3]),
                    'feature_type' => implode("|", $eff_detail[4]),
                    'feature_id' => implode("|", $eff_detail[5]),
                    'transcript_biotype' => implode("|", $eff_detail[6]),
                    'flank_left' => $flank_left['sequences'],
                    'flank_right' => $flank_right['sequences'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            ]);

            $submitted_count++;
        }

        fclose($vcf_handle);
        fclose($flank_handle);
        
        fwrite(STDERR, "Operation Success\n $submitted_count SNP submitted\n");
    }
}
