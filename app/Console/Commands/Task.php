<?php

namespace App\Console\Commands;

#use App\Model\Utenti;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

class Task extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'symbol:shake {rows?}';
    protected $symbols = [9, 10, 'J', 'Q', 'K', 'A', 'cat', 'dog', 'monkey', 'bird'];
    protected $rows = [[0,3,6,9,12],[1,4,7,10,13],[2,5,8,11,14],[0,4,8,10,12],[2,4,6,10,14]];
    protected $wins = [3 => 20, 4 => 200, 5 => 1000];
    protected $bet_amount = 100;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Play with video slot';

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
        $rows = (int)$this->argument('rows');
        
        if(empty($rows)){
            $rows = 3;
        }
        
        $card_all = [];
        for($i=1;$i<=$rows;$i++){
            
            $card = [];
            for($j=1; $j<=5; $j++){
                $index = rand(0,5);
                $card[] = (String)$this->symbols[$index];
            }
            //$this->info('symbol mixed (' . $i . '): ' . print_r($card,true));
            $card_all[] = $card;
            
            //$this->line('-----------------------------------------------------');
        }
        
        
        $separator = new TableSeparator;

        
        $table = new Table($this->output);
        $table->setHeaders([new TableCell('Hand Primary Random', ['colspan' => 5])]);
        $table->setRows($card_all);
        $table->render();

        $this->line(PHP_EOL);

        $table = new Table($this->output);
        $table->setHeaders([new TableCell('Hand Matrix', ['colspan' => 5])]);
        $table->setRows($this->rows);
        $table->render();

        $this->line(PHP_EOL);
        
        
        $card_sort = [];
        $card_index = 0;
        
        $card_all_str = '';
        foreach($card_all as $card){
            
            for($jj = 0;$jj < count($card); $jj++){
                $card_sort[$jj.$card_index] = $card[$jj];
            }
            $card_index++;
            
            // boards string
            $card_all_str.= implode(',',$card) . ',';
        }

        ksort($card_sort);
        $card_sort = array_values($card_sort);


        $table_row = [];
        foreach($this->rows as $arow){
            
            $acol = [];
            foreach($arow as $k => $col){
                $acol[] = $card_sort[$col];
            }

            $table_row[] = $acol;
        }



        $table = new Table($this->output);
        $table->setHeaders([new TableCell('Hand New Primary', ['colspan' => 5])]);
        $table->setRows($table_row);
        $table->render();
        $this->line(PHP_EOL);


        // we are looking for the winner
        $table_row_new = [];
        $table_array_win = [];
        $table_row_new_str = '';
        $wins_total = 0;
        foreach($table_row as $k_ey => $cards){
            $card_unique_count = count(array_unique($cards));
            $value = 0;
            if($card_unique_count<5){
                
                $acols = [];
                $card_unique_one = array_count_values($cards);
                foreach($card_unique_one as $card_duplicate => $value){
                    
                    $card_duplicate_tag = $card_duplicate;
                    
                    if($value>2){
                        $card_duplicate_tag = '<fg=red;bg=yellow>' . $card_duplicate . '</>';
                        $table_array_win[] = [($k_ey+1), $value, $this->wins[$value], ($this->bet_amount * $this->wins[$value]/100)];
                        $wins_total+= ($this->bet_amount * $this->wins[$value] / 100);
                    }

                    for($ii=0;$ii<$value;$ii++){
                        $acols[] = $card_duplicate_tag;
                    }
                }
                $table_row_new[] = $acols;

            }else{
                $table_row_new[] = $cards;
            }

            $table_row_new_str.= '{"' . implode(' ', $cards).'":' . ($value > 2 ? $value : 0) . '},';
        }

        $table = new Table($this->output);
        $table->setHeaders([new TableCell('Hand Match', ['colspan' => 5])]);
        $table->setRows($table_row_new);
        $table->render();

        $this->line(PHP_EOL);
        
        $table = new Table($this->output);
        $table->setHeaders([[new TableCell('Winner with nr of row', ['colspan' => 4])],['hand','match','%','win']]);
        
        if(!empty($table_array_win)){
            $table->setRows($table_array_win);
        }else{
            $table->setRows([$separator]);
        }
        
        $table->render();
        
        $output = [
            'boards' => trim($card_all_str, ','),
            'paylines' => trim($table_row_new_str, ','),
            'bet_amount' => $this->bet_amount,
            'total_win' => $wins_total
        ];
        
        
        $this->line(PHP_EOL);
        $output_str = json_encode($output, JSON_PRETTY_PRINT);
        $this->info($output_str);


    }
}
