<html>
    <body>

        @if($exibelinkConfirmar and $mostraTituloPadrao == 1)
        <h1 style='color: #337ab7;
            margin-bottom: 13px;
            font-size: 29px;'> Agendamento de consulta com <b>{{$nomeDoutor}} </b></h1>
        <br>

        @endif

        @if (isset($linkVideo) and !empty($linkVideo)) 
        <table >
            <tr>
                <td><b>Consulta por video</b></td>
            </tr>
            <tr>
                <td>

                    <a style='  background-color: #b1e2b1;
                       padding: 15px;
                       -webkit-border-radius: 30px;
                       -moz-border-radius: 30px;
                       border-radius: 30px;
                       width: 100%;
                       text-align: center;
                       color: #1d481e;
                       font-weight: bold;
                       font-size: 15px;
                       text-decoration: none;' href='{!!$linkVideo!!}'>Acesso para Videoconsulta</a>
                </td>
                <td> <div ><a style='  color:  #339966;
                              font-weight: bold;' href='{!!$linkConfirmacao!!}'>Clique aqui para confirmar a sua consulta</a> .</div>
                </td>
            </tr>
        </table>
        <br><br>


        @endif
        @if ( isset($linkPagseguro) and !empty($linkPagseguro)) 
        <table >


            <tr>
                <td> <b>Preço da consulta: </b>R$  {{$precoConsulta}} </td>
            </tr>
            <tr>


                <td> Clique aqui para fazer o pagamento:</td>
                <td><a style="padding: 7px;
                       background-color: #ff907e;
                       color: #FFF;
                       text-decoration:none;
                       font-weight: bold;
                       -webkit-border-radius: 30px;
                       -moz-border-radius: 30px;
                       border-radius: 30px;
                       font-size: 13px;" href="{!!$linkPagseguro!!}">Pague agora</a></td>


            </tr>

        </table>
        <br>
        @endif
        {!!$mensagem!!}

        @if($exibelinkConfirmar and $mostraDadosConsulta == 1)
        <br/><br/>  
        <div style=' text-decoration:underline;
             font-weight:bold;
             color:#000;
             font-size:14px;'><b> Dados da Consulta:</b></div> 
        <b>Data:</b> {{$data}}<br/> 
        <b>Hora:</b>  {{$horario}} <br/>
        <b>Doutor(a):</b> {{$nomeDoutor}}<br/>
        <b>Clinica:</b> {{$nomeClinica}}<br/><br/>
        @endif


        @if(isset($linkConfirmacao) and !empty($linkConfirmacao))
        <div ><a style='  color:  #339966;
                 font-weight: bold;' href='{{$linkConfirmacao}}'>Clique aqui para confirmar a sua consulta</a> .</div>
        @endif





        @if ($linkRemarcar and $mostraLinkDesmarcacao == 1) 
        <div > <a i style=' color:  #FF6600;
                  font-weight: bold;' href = '{{$linkDesmarcar}}'>Caso tenha cometido um engano e deseja desmarcar a consulta clique aqui</a>. </div>
        @endif




        <br><br><br>
        @php echo $imagem_assinatura @endphp
        <br><br>

        @if (isset($mostraLinksSociais) and $mostraLinksSociais == 1) 

        @if (isset($dadosLinksSociais['facebook']) and !empty($dadosLinksSociais['facebook']['link']))

        <a href="@php echo $dadosLinksSociais['facebook']['link']@endphp"  style="float:left;"><img src="@php echo $dadosLinksSociais['facebook']['icon']@endphp"  style="width:20px" alt="Facebook"/></a>
        @endif  
        @if (isset($dadosLinksSociais['linkedin']) and !empty($dadosLinksSociais['linkedin']['link']))

        <a href="@php echo $dadosLinksSociais['linkedin']['link']@endphp"  style="float:left;"><img src="@php echo $dadosLinksSociais['linkedin']['icon']@endphp"  style="width:20px" alt="Linkedin"/></a>
        @endif  
        @if (isset($dadosLinksSociais['twitter']) and !empty($dadosLinksSociais['twitter']['link']))

        <a href="@php echo $dadosLinksSociais['twitter']['link']@endphp"  style="float:left;"><img src="@php echo $dadosLinksSociais['twitter']['icon']@endphp"  style="width:20px" alt="Twitter"/></a> 
        @endif  
        <br><br>
        @endif

        Você recebeu este e-mail porque você é um cliente da clínica<b>{{$nomeClinica}}</b>


        <div >Este e-mail não é para você? <a href='$this->linkBloqueiaEmail' onclick='return false;'>Cancelar recebimento</a> </div>



    </body>
</html>