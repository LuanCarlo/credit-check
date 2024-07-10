<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

class CheckCreditOffersController extends Controller
{

    /**
     * função responsavel por buscar intituições de credito disponiveis para o cpf
    */
    public function getInstituitionsByCpf(Request $request)
    {

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
        /*$validation = Validator::make($request->all(), [
            'usuario_id' => 'integer'
        ]);*/

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

        $totalsCalculateds = $this->calculInterestRate($request->input('value'), $request->input('installments'), $request->input('jurosMes'));

        $calcReturn = [
            'instituicaoFinanceira'=>$request->input('instituicaoFinanceira'),
            'modalidadeCredito'=>$request->input('modalidadeCredito'),
            'valorAPagar'=>$totalsCalculateds['totalPagar'],
            'taxaJuros'=>$totalsCalculateds['totalJuros'],
            'modalidadeCredito'=>$request->input('value'),
            'qntParcelas'=>$request->input('installments'),
        ];
        return json_encode(['status'=>200, 'record'=>$calcReturn]);

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

        $modalities = [];

        foreach ($creditOffers as $offerData) {
            foreach ($offerData->modalidades as $modality) {
                
                //busca dados detalhados da oferta
                $detailedOffer = $this->getDetailedOffer($cpf, $offerData->id, $modality->cod);
                $detailedOffer->instituicaoFinanceira = $offerData->nome;
                $detailedOffer->instituicaoFinanceiraId = $offerData->id;
                $detailedOffer->modalidadeCredito = $modality->nome;
                $detailedOffer->modalidadeCreditoCod = $modality->cod;
                $creditOffersDetailed[] = $detailedOffer;
            }
        }

        usort($creditOffersDetailed, function($a, $b) {
            if ($a->jurosMes == $b->jurosMes) {
                return $b->valorMax <=> $a->valorMax;
            }
            return $a->jurosMes <=> $b->jurosMes;
        });

        return $creditOffersDetailed;
       
    }  



    private function calcInterestRate($value, $installments, $interestRate)
    {
        $installments = ($value * $interestRate) / (1 - pow((1 + $interestRate), - $installments));
        return $installments;
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
