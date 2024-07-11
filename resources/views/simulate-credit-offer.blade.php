<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simulação de Crédito</title>

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>

        .divBordered {
            border: solid black 1px;
        }

        .active {
            border: solid blue 2px;
        }

        .hide {
            display: none;
        }

        .pointer {
            cursor: pointer;
        }

    </style>
</head>
<body>

   <div class="container">
        <h1>Simulação de Oferta de crédito</h1>
        <p>Faça aqui sua simulação.</p>

        <div class="form-group">
            <div class="col-sm-6 mb-3">
                <label for="cpf">CPF</label>
                <input type="text" class="form-control" id="cpf" placeholder="Digite seu CPF">
            </div>
            <div class="col-sm-3 mb-3">
                <button id="getInstituitions" class="btn btn-primary">Buscar opções de crédito</button>
            </div>
        </div>

        <div class="col-sm-6 mb-3">

            <div id="institutions-container" class="mt-3 mb-3"></div>

            <div class="form-group hide mt-3">
                <div class="col-sm-6 mb-3">
                    <label for="valor">Valor</label>
                    <input type="text" class="form-control" id="value" placeholder="Digite o valor desejado">
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="valor">Parcelas</label>
                    <input type="text" class="form-control" id="installments" placeholder="Digite o número de parcelas">
                </div>

                <div id="validation-container"></div>

                <div class="col-sm-6 mb-3">
                    <button id="getCalc" class="btn btn-primary">Calcular</button>
                </div>
            </div>


            <div class="card mt-6">
                <div id="responseCalc-container" class="card-body hide"></div>
            </div>
        </div>
    </div>
    

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });


        $(document).ready(function() {

            let cpf = null;
            let intituitions = [];
            let optionSelected = {};
            let conditionsCalculateds = {};
            $('#cpf').mask('000.000.000-00');

            $('#getInstituitions').click(function() {
                const cpf = $('#cpf').val();
                
                $.ajax({
                    url: 'http://127.0.0.1:8000/api/getBestCreditOffersByCpf',
                    method: 'POST',
                    data: { cpf: cpf },
                    success: function(response) {
                        
                        if (response) {

                            const jsonObject = JSON.parse(response);
                            intituitions = jsonObject.record;

                            const institutionsContainer = $('#institutions-container');
                            institutionsContainer.empty();

                            institutionsContainer.append('<p class="mt-3">Selecione ama opção :</p>');
                            
                            intituitions.forEach(function(institution, idx) {
                                let institutionDiv = $('<div class="card-body" id="'+idx+'" ></div>');
                                
                                // Construir o conteúdo da div com base nos atributos do objeto
                                institutionDiv.append('<h3>' + institution.instituicaoFinanceira + '</h3>');
                                institutionDiv.append('<p>Modalidade de Crédito: ' + institution.modalidadeCredito + '</p>');
                                institutionDiv.append('<p>Juros ao Mês: ' + institution.jurosMes + '</p>');
                                institutionDiv.append('<p>Valor Mínimo: ' + institution.valorMin + '</p>');
                                institutionDiv.append('<p>Valor Máximo: ' + institution.valorMax + '</p>');
                                institutionDiv.append('<p>Número Mínimo de Parcelas: ' + institution.QntParcelaMin + '</p>');
                                institutionDiv.append('<p>Número Máximo de Parcelas: ' + institution.QntParcelaMax + '</p>');


                                let institutionCard = $('<div class="card institution mt-3 pointer"></div>');
                                institutionCard.append(institutionDiv)

                                institutionCard.click(function() {                                    

                                    $('.institution').removeClass('active');
                                    $(this).addClass('active');

                                    $('.institution').addClass('hide');
                                    $(this).removeClass('hide');

                                    $('.form-group').removeClass('hide');

                                    let valueField = document.getElementById('value');
                                    valueField.setAttribute('min', institution.valorMin);
                                    valueField.setAttribute('max', institution.valorMax);

                                    let installmentField = document.getElementById('installments');
                                    installmentField.setAttribute('min', institution.QntParcelaMin);
                                    installmentField.setAttribute('max', institution.QntParcelaMax);


                                    selectCreditOptionsDetailed(cpf, institution);
                                });

                                institutionsContainer.append(institutionCard);
                                
                            });
                        }
                    },
                    error: function(xhr) {

                        let msg = 'Erro ao tentar buscar dados';
                        if(xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }

                        $('#institutions-container').html('<div class="alert alert-danger">'+msg+'</div>');
                    }
                });
            });

            $('#value').on('blur', function() {
                var min = parseFloat($(this).attr('min'));
                var max = parseFloat($(this).attr('max'));
                var value = parseFloat($(this).val());

                if (value < min) {
                    $(this).val(min);
                } else if (value > max) {
                    $(this).val(max);
                }
            });

            $('#installments').on('blur', function() {
                var min = parseFloat($(this).attr('min'));
                var max = parseFloat($(this).attr('max'));
                var value = parseFloat($(this).val());

                if (value < min) {
                    $(this).val(min);
                } else if (value > max) {
                    $(this).val(max);
                }
            });

            function selectCreditOptionsDetailed(cpf,instituition) {
                optionSelected = instituition; 
            }

            $('#getCalc').click(function() {

                const cpf = $('#cpf').val();
                const value = $('#value').val();
                const installments = $('#installments').val();
                optionSelected.cpf = cpf;
                optionSelected.value = value;
                optionSelected.installments = installments;
                
                $.ajax({
                    url: 'http://127.0.0.1:8000/api/calculateCreditConditions',
                    method: 'POST',
                    data: optionSelected,
                    success: function(response) {
                        
                        if (response) {

                            const jsonObject = JSON.parse(response);
                            conditionsCalculateds = jsonObject.record;

                            renderRetunCalc(conditionsCalculateds);
                        }
                    },
                    error: function(xhr) {

                        console.log(xhr);

                        let msg = 'Erro ao tentar buscar dados';
                        if(xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }

                        $('#validation-container').html('<div class="alert alert-danger">'+msg+'</div>');
                    }
                });
            });

            function renderRetunCalc(conditionsCalculateds) {
                
                console.log('conditionsCalculateds', conditionsCalculateds);

                const validationContainer = $('#validation-container');
                validationContainer.empty();

                const responseCalcContainer = $('#responseCalc-container');
                responseCalcContainer.empty();


                $('#responseCalc-container').removeClass('hide');

                var responseCalcDiv = $('#responseCalc-container');
                responseCalcDiv.append('<p>Instituição Financeira: ' + conditionsCalculateds.instituicaoFinanceira + '</p>');
                responseCalcDiv.append('<p>Modalidade de Crédito: ' + conditionsCalculateds.modalidadeCredito + '</p>');
                responseCalcDiv.append('<p>Valor Solicitado: ' + conditionsCalculateds.valorSolicitado + '</p>');
                responseCalcDiv.append('<p>Valor a pagar: ' + conditionsCalculateds.valorAPagar + '</p>');
                responseCalcDiv.append('<p>Taxa Juros: ' + conditionsCalculateds.taxaJuros + '</p>');
                responseCalcDiv.append('<p>Qnt Parcelas: ' + conditionsCalculateds.qntParcelas + '</p>');
            }
        });
    </script>
    
</body>
</html>