
<style>
    label{
        font-weight: bold;
    }

    .box-links{
        margin:10px 10px 10px 0;
        padding: 5px;
        border: 2px solid #ccc;
        -webkit-border-radius: 15px;
        -moz-border-radius: 15px;
        border-radius: 15px;
    }
    h5 span{

        color:#4077a7;
        font-weight: bold;


    }
</style>

<div style="color:"></div>
<h4 style="">Olá <span>{{$name}}</span>!</h4>
<br>
<p>Abaixo está o código para alteração da senha:</p><br>



@if(count($links) >0)
@foreach($links as  $row)

<div class="box-links">
    <label><b>Clínica:</b></label> {{utf8_decode($row['clinica'])}}<br> 
    <label><b>Código:</b></label> <b style="color:#2472ff">{{$row['codigo']}}</b><br>
</div>

@endforeach
@endif