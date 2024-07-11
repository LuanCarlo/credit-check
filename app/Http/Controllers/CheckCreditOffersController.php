<?php

namespace App\Http\Controllers;

use App\Models\CreditHistory;
use App\Models\Instituitions;
use App\Models\Modality;
use Exception;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckCreditOffersController extends Controller
{
    public function simulateCreditOfferPage()
    {
        return view('simulate-credit-offer');
    }

    public function reportCreditOfferPage()
    {
        return view('reports-credit-offer');
    }

    public function getCreditValuesByMonth()
    {

        $startDate = now()->startOfYear();
        $endDate = now();
    
        $months = [];
        while ($startDate <= $endDate) {
            $months[] = $startDate->format('Y-m');
            $startDate->addMonth();
        }
    
        $creditValues = DB::table('credit_history')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(value_requested) as total_value')
            )
            ->whereBetween('created_at', [$months[0], now()])
            ->groupBy('month')
            ->get();
    
        $formattedValues = [];
        foreach ($months as $month) {
            $totalValue = $creditValues->firstWhere('month', $month);
            $formattedValues[] = [
                'month' => Carbon::createFromFormat('Y-m', $month)->format('F'),
                'total_value' => $totalValue ? $totalValue->total_value : 0
            ];
        }


        return json_encode(['status'=>200, 'record'=>$formattedValues]);
    }

    public function creditValuesByModality()
    {
     
        $creditValues = DB::table('credit_history')
            ->select('modality.nome as modalidade', DB::raw('SUM(credit_history.value_requested) as total_value'))
            ->join('modality', 'credit_history.modality', '=', 'modality.id')
            ->groupBy('modality.nome')
            ->get();


        foreach ($creditValues as $value) {
            $formattedValues[] = [
                'modality' => $value->modalidade, 
                'total_value' =>(float) $value->total_value
            ];
        }
    
        return json_encode(['status'=>200, 'record'=>$formattedValues]);
    }


    public function creditSimulationsByInstituition()
    {
     
        $creditValues = DB::table('credit_history')
            ->select('instituitions.nome as instituition', DB::raw('count(credit_history.id) as total_simulations'))
            ->join('instituitions', 'credit_history.instituition_id', '=', 'instituitions.id')
            ->groupBy('instituitions.nome')
            ->get();


        foreach ($creditValues as $value) {
            $formattedValues[] = [
                'instituition' => $value->instituition, 
                'total_simulations' =>(float) $value->total_simulations
            ];
        }
    
        return json_encode(['status'=>200, 'record'=>$formattedValues]);
    }

    /**
     * função responsavel por buscar intituições de credito disponiveis para o cpf
    */
    public function getInstituitionsByCpf(Request $request)
    {

        $request->validate([
            'cpf' => 'required',
        ]);

        $cpf = $request->input('cpf');

        $creditoffersAvaliable = $this->getCreditOffersByCpf($cpf);
      
        return json_encode(['status'=>200, 'record'=>$creditoffersAvaliable]);
    }


    private function getCreditOffersByCpf($cpf)
    {

        $data = ['cpf'=>$cpf];
        //busca ofertas de crédito
        $offers = $this->getExternalData($data, 'https://dev.gosat.org/api/v1/simulacao/credito');

        return $offers->instituicoes;
    }
    
    /**
     * Função responsável por buscar a melhor oferta de crédito para um cpf
     *
     * @return \Illuminate\Http\Response
     * @param  \Illuminate\Http\Request  $request
    */
    public function getBestCreditOffersByCpf(Request $request)
    {
        //
        $request->validate([
            'cpf' => 'required',
        ]);

        $cpf = $request->input('cpf');
        $creditoffersAvaliable = $this->getCreditOffersByCpf($cpf);        
        $creditoffersDetailed = $this->getCreditDetailedDataByOffer($creditoffersAvaliable, $cpf);
        $return = $creditoffersDetailed;
      
        return json_encode(['status'=>200, 'record'=>$return]);
    }

    public function getDetailByOffers(Request $request)
    {
        //busca dados detalhados da oferta
        $detailedOffer = $this->getDetailedOffer($request->input('cpf'), $request->input('instituicao_id'), $request->input('codModalidade'));

        return json_encode(['status'=>200, 'record'=>$detailedOffer]);
    }

    public function getCalcByOffer(Request $request)
    {
        $request->validate([
            'cpf' => 'required',
            'instituicao_id' => 'required',
            'codModalidade' => 'required',
        ]);

        //busca dados detalhados da oferta
        $detailedOffer = $this->getDetailedOffer($request->input('cpf'), $request->input('instituicao_id'), $request->input('codModalidade'));

        if (($request->input('value') < $detailedOffer->valorMin) || ($request->input('value') > $detailedOffer->valorMax)) {
            return json_encode(['status'=>422, 'record'=>['errors'=>1, 'message'=>'Valor fora do limite da opção']]);
        }

        if (($request->input('installments') < $detailedOffer->valorMin) || ($request->input('installments') > $detailedOffer->valorMax)) {
            return json_encode(['status'=>422, 'record'=>['errors'=>1, 'message'=>'Parcela fora do limite da opção']]);
        }
        

        $totalsCalculateds = (object) $this->calculInterestRate($request->input('value'), $request->input('installments'), $detailedOffer->jurosMes);
        $intituition = Instituitions::find($request->input('instituicao_id'));
        $modality = Modality::find($request->input('codModalidade'));

        $intituitionLabel = $request->input('instituicao_id');
        $modalityLabel = $request->input('instituicao_id');
        if ($intituition) {
            $intituitionLabel = $intituition->nome;
        }
        if ($modality) {
            $modalityLabel = $modality->nome;
        }

        $calcReturn = (object) [
            'instituicaoFinanceira'=>$intituitionLabel,
            'modalidadeCredito'=>$modalityLabel,
            'valorSolicitado'=>$request->input('value'),
            'valorAPagar'=>$totalsCalculateds->totalPagar,
            'taxaJuros'=>$totalsCalculateds->totalJuros,
            'qntParcelas'=>$request->input('installments'),
        ];

        return json_encode(['status'=>200, 'record'=>$calcReturn]);
    }


    /**
     * Função responsável por buscar detalhes da oferta
     */
    private function getDetailedOffer($cpf, $instituition, $cod)
    {

        $offerDataTOGetDatail = [
            'cpf'=>$cpf,
            'instituicao_id'=>$instituition,
            'codModalidade'=>$cod
        ];


        //busca dados detalhados da oferta
        return  $this->getExternalData($offerDataTOGetDatail, 'https://dev.gosat.org/api/v1/simulacao/oferta');
    }
    

    public function calculateCreditConditions(Request $request)
    {
    
        /*
        o instituicaoFinanceira
        o modalidadeCredito
        o valorAPagar
        o valorSolicitado
        o taxaJuros
        o qntParcelas*/

        $request->validate([
            'value' => 'required',
            'installments' => 'required',
        ]);

        $totalsCalculateds = $this->calculInterestRate($request->input('value'), $request->input('installments'), $request->input('jurosMes'));

        $calcReturn = (object) [
            'instituicaoFinanceira'=>$request->input('instituicaoFinanceira'),
            'modalidadeCredito'=>$request->input('modalidadeCredito'),
            'valorSolicitado'=>$request->input('value'),
            'valorAPagar'=>$totalsCalculateds['totalPagar'],
            'taxaJuros'=>$totalsCalculateds['totalJuros'],
            'qntParcelas'=>$request->input('installments'),
        ];

        $return = $this->saveUserHistory(
            $request->input('cpf'),
            $request->input('instituicaoFinanceiraId'), 
            $request->input('modalidadeCreditoCod'), 
            $calcReturn
        );

        return json_encode(['status'=>200, 'record'=>$calcReturn]);

    }

    /**
     * função responsável por salvar o historico de simulações
     */
    public function saveUserHistory($cpf, $instituicaoFinanceiraId, $modalidadeCreditoCod, $calcData)
    {
        try {

            $creditHistory = CreditHistory::create([
                'user_cpf' => $cpf,
                'instituition_id' => $instituicaoFinanceiraId,
                'modality' => $modalidadeCreditoCod,
                'value_requested' => $calcData->valorSolicitado,
                'installments' => $calcData->qntParcelas,
            ]);

            $response = $creditHistory;

        } catch(Exception $error) {

            $response = $error->getMessage();
        }

       return $response;
    }

    public function saveInstituitions($id, $instituition)
    {
        try {

            $response = null;
            $existingRecord = Instituitions::find($id);

            if (!$existingRecord) {
                $instituition = Instituitions::create([
                    'id' => $id,
                    'nome' => $instituition,
                ]);
                $response = $instituition;
            }

        } catch(Exception $error) {

            $response = $error->getMessage();
        }

       return $response;
    }

    public function saveModality($id, $instituition)
    {
        try {

            $response = null;
            $existingRecord = Modality::find($id);

            if (!$existingRecord) {
                $instituition = Modality::create([
                    'id' => $id,
                    'nome' => $instituition,
                ]);
                $response = $instituition;
            }

        } catch(Exception $error) {

            $response = $error->getMessage();
        }

       return $response;
    }

    
    private function calculInterestRate($value, $installments, $interetRate) {
        $totalValue = $value + ($value * $installments * $interetRate);
        $totalInteretRate = $totalValue - $value;
        
        return [
            'totalPagar' => $totalValue,
            'totalJuros' => $totalInteretRate
        ];
    }
    

    private function getCreditDetailedDataByOffer($creditOffers, $cpf)
    {
        $creditOffersDetailed = [];

        foreach ($creditOffers as $offerData) {
            foreach ($offerData->modalidades as $modality) {
                
                //busca dados detalhados da oferta
                $detailedOffer = $this->getDetailedOffer($cpf, $offerData->id, $modality->cod);
                $detailedOffer->instituicaoFinanceira = $offerData->nome;
                $detailedOffer->instituicaoFinanceiraId = $offerData->id;
                $detailedOffer->modalidadeCredito = $modality->nome;
                $detailedOffer->modalidadeCreditoCod = $modality->cod;
                $creditOffersDetailed[] = $detailedOffer;

                $this->saveModality($modality->cod, $modality->nome);
            }
            $this->saveInstituitions($offerData->id, $offerData->nome);
        }

        usort($creditOffersDetailed, function($a, $b) {
            if ($a->jurosMes == $b->jurosMes) {
                return $b->valorMax <=> $a->valorMax;
            }
            return $a->jurosMes <=> $b->jurosMes;
        });

        return $creditOffersDetailed;
       
    }  


    private function getExternalData($fields, $url)
    {

        $data = json_encode($fields);

        try {

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
             ),
            ));
    
            $response = json_decode(curl_exec($curl));
    
            curl_close($curl);

        } catch(Exception $error) {

            $response = $error->getMessage();
        }
       
        return $response;
    }
}
