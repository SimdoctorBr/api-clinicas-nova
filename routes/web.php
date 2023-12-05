<?php

/** @var \Laravel\Lumen\Routing\Router $router */
/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 * 
 */


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

//
//$router->group(['prefix' => '/v1', 'namespace' => 'TuoTempo\Clinicas'], function () use ($router) {
//    $router->post('register', 'AuthController@register');
//    $router->post('login', 'AuthController@login');
//    $router->get('/docs/download/', 'PacienteController@downloadEpisodeDoc');
//});
//
//
//$router->group(['middleware' => 'auth', 'prefix' => 'v1', 'namespace' => 'TuoTempo\Clinicas'], function($router) {
//    $router->get('/getLocations', 'EmpresaController@index');
//    $router->get('/getResources/', 'ResourceController@index');
//    $router->get('/getActivities/', 'ActivityController@index');
//    $router->get('/getInsurances/', 'InsuranceController@index');
//    $router->get('/getUsers/', 'PacienteController@index');
//    $router->get('/getAppointments/', 'ConsultaController@index');
//    $router->post('/cancelAppointment/', 'ConsultaController@cancelar');
//
//    $router->get('/getAvailabilities/', 'AgendaController@getHorariosDisponiveis');
//    $router->post('/addAppointment/', 'AgendaController@adicionarConsulta');
//    $router->post('/rescheduleAppointment/', 'AgendaController@remarcarConsulta');
//    $router->post('/updateAppointment/', 'AgendaController@atualizarConsulta');
//    $router->post('/fastCheckin/', 'AgendaController@fastCheckin');
//
//    $router->post('/notifyPayment/', 'PaymentController@notificarPagamento');
//    $router->get('/getPaymentDocuments/', 'PaymentController@buscarPagamento');
//
//    $router->get('/getEpisodes/', 'PacienteController@getEpisodes');
//    $router->get('/getEpisodeDocuments/', 'PacienteController@getEpisodeDocuments');
//    $router->get('/getNotificationUsers/', 'PacienteController@getNotificationUsers');
//});
//
///////teste


$router->group(['prefix' => '/api/clinicas', 'namespace' => 'ApiClinicas'], function () use ($router) {



//    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->post('loginPerfil', 'AuthController@loginPorPerfil');
    $router->post('esqueciSenha', 'AuthController@esqueciSenha');
//    $router->get('esqueciSenha', 'AuthController@esqueciSenhaVerificaCodigo');

    $router->post('esqueciSenhaPaciente', 'esqueciSenhaPaciente');
//    $router->get('esqueciSenhaPaciente', 'AuthController@esqueciSenhaPacienteVerificaCodigo');



    $router->post('registrarPacienteLogin', 'AuthController@registerPacienteLogin');
    $router->post('esqueciSenhaPaciente', 'AuthController@esqueciSenhaPaciente');
    $router->get('esqueciSenhaPaciente', 'AuthController@esqueciSenhaVerificaCodigoPaciente');

//    $router->post('esqueciSenha',  function() {
//
// dd($details);
//        $details = [
//            'title' => 'Mail from ItSolutionStuff.com',
//            'body' => 'This is for testing email using smtp'
//        ];
////        return 'teste';
//      Mail::send
////        return new \App\Mail\MailEsqueciSenhaApi($details);
//    });
});

$router->group(['middleware' => 'auth:clinicas', 'prefix' => 'api/clinicas', 'namespace' => 'ApiClinicas'], function ($router) {

    $router->get('/me', 'AuthController@me');
    $router->post('logout', 'AuthController@logout');
    $router->post('alterarSenha', 'AuthController@alterarSenha');

    //Especialides
    $router->group(['prefix' => 'especialidades'], function ($router) {

        $router->get('/', 'EspecialidadeController@index');
    });

    $router->get('/gruposAtendimento', 'GrupoAtendimentoController@index');
    $router->get('/idiomas', 'IdiomaClinicaController@index');

    //Agenda
    $router->group(['prefix' => 'agenda'], function ($router) {
        $router->get('/filaEspera/{doutorId}', 'AgendaController@filaEspera');
        $router->get('/calendarioPreenchido/{doutorId}', 'AgendaController@percentualCalPreenchido');
        $router->get('/calendarioDiasDisponibilidade/{doutorId}', 'AgendaController@calendarioDiasDisponibilidade');
        $router->get('/listaHorarios/{doutorId}', 'AgendaController@listaHorarios');
        $router->get('/resumoDiario/{doutorId}', 'AgendaController@resumoDiario');
    });

    //Administracao
    $router->group(['prefix' => 'administracao'], function ($router) {
        $router->get('/lgpdAutorizacoes', 'Administracao\LgpdAutorizacoesController@index');
        $router->get('/lgpdAutorizacoes/termosCondicoes', 'Administracao\LgpdAutorizacoesController@getTermosCondicoes');
    });

    //Consultas
    $router->group(['prefix' => 'consultas'], function ($router) {
        $router->get('/', 'ConsultaController@index');
        $router->post('/', 'ConsultaController@store');
        $router->post('/{consultaId}/confirmar', 'ConsultaController@confirmar'); //ok       
        $router->post('/{consultaId}/desmarcar', 'ConsultaController@desmarcar'); //ok  
        $router->post('/{consultaId}/alterarStatus', 'ConsultaController@alterarStatus'); //ok       
        $router->post('/alterar/{consultaId}', 'ConsultaController@update');
        $router->get('/{consultaId}', 'ConsultaController@getById');
        $router->delete('/{consultaId}', 'ConsultaController@delete');
    });
    $router->group(['prefix' => 'compromissos'], function ($router) {
        $router->get('/', 'CompromissoController@index');
        $router->get('/{compromissoId}', 'CompromissoController@getById');
        $router->put('/{compromissoId}', 'CompromissoController@update');
        $router->post('/', 'CompromissoController@store');
        $router->post('/{compromissoId}/alterarStatus', 'CompromissoController@alterarStatus');
        $router->delete('/{compromissoId}', 'CompromissoController@delete');
    });

    $router->group(['prefix' => 'financeiro'], function ($router) {

        $router->group(['prefix' => 'relatorios'], function ($router) {
            $router->get('/relMensalPdf', 'Financeiro\RelatorioController@relMensalPdf');
            $router->get('/relDiarioDoutor', 'Financeiro\RelatorioController@relDiarioDoutor');
            $router->get('/relDiarioDoutorCalendario', 'Financeiro\RelatorioController@relDiarioDoutorCalendario');
        });
        $router->group(['prefix' => 'formasPagamentos'], function ($router) {
            $router->get('/', 'Financeiro\FormaPagamentoController@getAll');
        });
        $router->group(['prefix' => 'recebimentos'], function ($router) {
            $router->post('/', 'Financeiro\RecebimentoController@store');
        });
    });
    $router->group(['prefix' => 'pacientes'], function ($router) {

        $router->get('/', 'Paciente\PacienteController@index'); //ok
        $router->post('/', 'Paciente\PacienteController@store');
        $router->get('/{pacienteId}', 'Paciente\PacienteController@getById');
        $router->post('/{pacienteId}/alteraFotoPerfil', 'Paciente\PacienteController@alteraFotoPaciente');
        $router->post('/{pacienteId}/atualizar', 'Paciente\PacienteController@update');
        $router->get('/{pacienteId}/consultas/proximas', 'Paciente\PacienteController@proximasConsultas');
        $router->get('/{pacienteId}/consultas/historico', 'Paciente\PacienteController@getHistoricoConsulta');
        $router->get('/{pacienteId}/dependentes', 'Paciente\PacienteController@getDependentes');

        $router->get('/{pacienteId}/fotos', 'Paciente\PacienteFotoController@index');
        $router->post('/{pacienteId}/fotos', 'Paciente\PacienteFotoController@store');
        $router->delete('/{pacienteId}/fotos/{fotoId}', 'Paciente\PacienteFotoController@delete');
        $router->post('/{pacienteId}/fotos/{fotoId}/edit', 'Paciente\PacienteFotoController@update');

        $router->get('/{pacienteId}/arquivos', 'Paciente\PacienteArquivoController@index');
        $router->post('/{pacienteId}/arquivos', 'Paciente\PacienteArquivoController@store');
        $router->post('/{pacienteId}/arquivos/{arquivoId}/edit', 'Paciente\PacienteArquivoController@update');
        $router->delete('/{pacienteId}/arquivos/{arquivoId}', 'Paciente\PacienteArquivoController@delete');

        $router->post('/{pacienteId}/prontuarioSimples', 'Paciente\PacienteProntuarioController@storeProntuarioSimplesAvulso');
        $router->get('/{pacienteId}/prontuariosUnificados', 'Paciente\PacienteProntuarioController@getHistoricoUnificado');

        $router->post('/{pacienteId}/prontuarios/{prontuarioId}/observacao', 'Paciente\PacienteProntuarioController@storeObservacao');
        $router->get('/{pacienteId}/prontuarios/{prontuarioId}/observacao', 'Paciente\PacienteProntuarioController@getObservacoes');
        $router->get('/{pacienteId}/prontuarios/{prontuarioId}/observacao/{observacaoId}', 'Paciente\PacienteProntuarioController@getObservacoesById');

        $router->get('/{pacienteId}/convenios', 'Paciente\PacienteConvenioController@index');
        $router->post('/{pacienteId}/convenios', 'Paciente\PacienteConvenioController@store');
        $router->post('/{pacienteId}/convenios/{convenioId}/atualizar', 'Paciente\PacienteConvenioController@update');
        $router->delete('/{pacienteId}/convenios/{convenioId}', 'Paciente\PacienteConvenioController@delete');

        $router->get('/{pacienteId}/planoBeneficios', 'Paciente\PacientePlBeneficioController@index');
        $router->post('/{pacienteId}/planoBeneficios', 'Paciente\PacientePlBeneficioController@contrataPlano');
        $router->post('/{pacienteId}/planoBeneficios/alterar', 'Paciente\PacientePlBeneficioController@alterarPlano');
        $router->post('/{pacienteId}/planoBeneficios/cancelarAlteracaoPlano', 'Paciente\PacientePlBeneficioController@cancelarAlteracaoPlano');
        $router->get('/{pacienteId}/planoBeneficios/ativo', 'Paciente\PacientePlBeneficioController@planoAtivo');
        $router->get('/{pacienteId}/planoBeneficios/{plBeneficioContratadoId}/historicoPagamentos', 'Paciente\PacientePlBeneficioController@planoHistoricoPagamento');
        $router->post('/{pacienteId}/codigo/enviarCodigo', 'Paciente\PacienteController@enviarCodigo');
        $router->post('/{pacienteId}/codigo/verificarCodigo', 'Paciente\PacienteController@verificarCodigo');
    });

    $router->group(['prefix' => 'doutores'], function ($router) {

        $router->get('/', 'Doutores\DoutoresController@index');

        $router->get('/{doutorId}', 'Doutores\DoutoresController@getById');
        $router->post('/{doutorId}/avaliacoes', 'Doutores\DoutoresController@storeAvaliacoes');

        $router->get('/{doutorId}/convenios', 'Doutores\DoutoresController@getConveniosDoutores');

        $router->get('/{doutorId}/fotos', 'Doutores\DoutoresFotoController@index');
        $router->post('/{doutorId}/fotos', 'Doutores\DoutoresFotoController@store');
        $router->delete('/{doutorId}/fotos/{fotoId}', 'Doutores\DoutoresFotoController@delete');
        $router->post('/{doutorId}/fotos/{fotoId}/edit', 'Doutores\DoutoresFotoController@update');
    });

    $router->group(['prefix' => 'configuracoes'], function ($router) {
        $router->get('/documentosExigidos', 'Configuracoes\DocumentosExigidosController@index');
    });
    $router->group(['prefix' => 'convenios'], function ($router) {
        $router->get('/', 'ConvenioController@index');
    });

    $router->group(['prefix' => 'planoBeneficio'], function ($router) {
        $router->post('/', 'PlanoBeneficioController@store');
        $router->get('/', 'PlanoBeneficioController@index');
        $router->post('/ativo', 'PlanoBeneficioController@getPlBeneficioPacienteAtivo');
        $router->get('/{idPlano}', 'PlanoBeneficioController@getById');
        $router->delete('/{idPlano}', 'PlanoBeneficioController@delete');
        $router->post('/{idPlano}/atualizar', 'PlanoBeneficioController@update');
    });

    $router->post('/agenda/atendimento/{consultaId}/iniciarAtendimento', 'AgendaController@iniciarAtendimento');
    $router->post('/agenda/atendimento/{consultaId}/salvarAtendimento', 'AgendaController@salvarAtendimento');
    $router->post('/agenda/bloqueioHorarios/{doutorId}', 'AgendaController@bloqueioRapidoAgenda');
    $router->post('/agenda/desbloqueioHorarios/{doutorId}', 'AgendaController@desbloqueioRapidoAgenda');
    $router->get('/agenda/verificaMudancaAgenda/{doutorId}', 'AgendaController@verificaMudancaAgenda');
    $router->get('/agenda/alertas', 'AgendaController@alertas');

    $router->get('/procedimentos/consultas/{consultaId}', 'ProcedimentoController@getByConsulta');
    $router->get('/procedimentos/doutores/{doutorId}', 'ProcedimentoController@getByDoutor');
    $router->get('/relatorios/agendamentos/', 'RelatorioController@getRelAgendamento');
    $router->get('/perfisUsuarios/', 'PerfisUsuariosController@index');
});

/////////////



$router->group(['prefix' => '/v1/clinicas', 'namespace' => 'ApiClinicas'], function () use ($router) {



//    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->post('loginPerfil', 'AuthController@loginPorPerfil');
    $router->post('esqueciSenha', 'AuthController@esqueciSenha');
//    $router->get('esqueciSenha', 'AuthController@esqueciSenhaVerificaCodigo');

    $router->post('esqueciSenhaPaciente', 'esqueciSenhaPaciente');
//    $router->get('esqueciSenhaPaciente', 'AuthController@esqueciSenhaPacienteVerificaCodigo');



    $router->post('registrarPacienteLogin', 'AuthController@registerPacienteLogin');
    $router->post('esqueciSenhaPaciente', 'AuthController@esqueciSenhaPaciente');
    $router->get('esqueciSenhaPaciente', 'AuthController@esqueciSenhaVerificaCodigoPaciente');

//    $router->post('esqueciSenha',  function() {
//
// dd($details);
//        $details = [
//            'title' => 'Mail from ItSolutionStuff.com',
//            'body' => 'This is for testing email using smtp'
//        ];
////        return 'teste';
//      Mail::send
////        return new \App\Mail\MailEsqueciSenhaApi($details);
//    });
});

$router->group(['middleware' => 'auth:clinicas', 'prefix' => 'v1/clinicas', 'namespace' => 'ApiClinicas'], function ($router) {

    $router->get('/me', 'AuthController@me');
    $router->post('logout', 'AuthController@logout');
    $router->post('alterarSenha', 'AuthController@alterarSenha');

    //Especialides
    $router->group(['prefix' => 'especialidades'], function ($router) {

        $router->get('/', 'EspecialidadeController@index');
    });

    $router->get('/gruposAtendimento', 'GrupoAtendimentoController@index');
    $router->get('/idiomas', 'IdiomaClinicaController@index');

    //Agenda
    $router->group(['prefix' => 'agenda'], function ($router) {
        $router->get('/filaEspera/{doutorId}', 'AgendaController@filaEspera');
        $router->get('/calendarioPreenchido/{doutorId}', 'AgendaController@percentualCalPreenchido');
        $router->get('/calendarioDiasDisponibilidade/{doutorId}', 'AgendaController@calendarioDiasDisponibilidade');
        $router->get('/listaHorarios/{doutorId}', 'AgendaController@listaHorarios');
        $router->get('/resumoDiario/{doutorId}', 'AgendaController@resumoDiario');
    });

    //Administracao
    $router->group(['prefix' => 'administracao'], function ($router) {
        $router->get('/lgpdAutorizacoes', 'Administracao\LgpdAutorizacoesController@index');
        $router->get('/lgpdAutorizacoes/termosCondicoes', 'Administracao\LgpdAutorizacoesController@getTermosCondicoes');
    });
    //Consultas
    $router->group(['prefix' => 'consultas'], function ($router) {
        $router->get('/', 'ConsultaController@index');
        $router->post('/', 'ConsultaController@store');
        $router->post('/{consultaId}/confirmar', 'ConsultaController@confirmar'); //ok       
        $router->post('/{consultaId}/desmarcar', 'ConsultaController@desmarcar'); //ok       
        $router->post('/{consultaId}/alterarStatus', 'ConsultaController@alterarStatus'); //ok       
        $router->post('/alterar/{consultaId}', 'ConsultaController@update');
        $router->get('/{consultaId}', 'ConsultaController@getById');
        $router->delete('/{consultaId}', 'ConsultaController@delete');
    });
    $router->group(['prefix' => 'compromissos'], function ($router) {
        $router->get('/', 'CompromissoController@index');
        $router->get('/{compromissoId}', 'CompromissoController@getById');
        $router->put('/{compromissoId}', 'CompromissoController@update');
        $router->post('/', 'CompromissoController@store');
        $router->post('/{compromissoId}/alterarStatus', 'CompromissoController@alterarStatus');
        $router->delete('/{compromissoId}', 'CompromissoController@delete');
    });

    $router->group(['prefix' => 'financeiro'], function ($router) {

        $router->group(['prefix' => 'relatorios'], function ($router) {
            $router->get('/relMensalPdf', 'Financeiro\RelatorioController@relMensalPdf');
            $router->get('/relDiarioDoutor', 'Financeiro\RelatorioController@relDiarioDoutor');
            $router->get('/relDiarioDoutorCalendario', 'Financeiro\RelatorioController@relDiarioDoutorCalendario');
        });
        $router->group(['prefix' => 'formasPagamentos'], function ($router) {
            $router->get('/', 'Financeiro\FormaPagamentoController@getAll');
        });
        $router->group(['prefix' => 'recebimentos'], function ($router) {
            $router->post('/', 'Financeiro\RecebimentoController@store');
        });
    });
    $router->group(['prefix' => 'pacientes'], function ($router) {

        $router->get('/', 'Paciente\PacienteController@index'); //ok
        $router->post('/', 'Paciente\PacienteController@store');
        $router->get('/{pacienteId}', 'Paciente\PacienteController@getById');
        $router->post('/{pacienteId}/alteraFotoPerfil', 'Paciente\PacienteController@alteraFotoPaciente');
        $router->post('/{pacienteId}/atualizar', 'Paciente\PacienteController@update');
        $router->get('/{pacienteId}/consultas/proximas', 'Paciente\PacienteController@proximasConsultas');
        $router->get('/{pacienteId}/consultas/historico', 'Paciente\PacienteController@getHistoricoConsulta');
        $router->get('/{pacienteId}/dependentes', 'Paciente\PacienteController@getDependentes');

        $router->get('/{pacienteId}/fotos', 'Paciente\PacienteFotoController@index');
        $router->post('/{pacienteId}/fotos', 'Paciente\PacienteFotoController@store');
        $router->delete('/{pacienteId}/fotos/{fotoId}', 'Paciente\PacienteFotoController@delete');
        $router->post('/{pacienteId}/fotos/{fotoId}/edit', 'Paciente\PacienteFotoController@update');

        $router->get('/{pacienteId}/arquivos', 'Paciente\PacienteArquivoController@index');
        $router->post('/{pacienteId}/arquivos', 'Paciente\PacienteArquivoController@store');
        $router->post('/{pacienteId}/arquivos/{arquivoId}/edit', 'Paciente\PacienteArquivoController@update');
        $router->delete('/{pacienteId}/arquivos/{arquivoId}', 'Paciente\PacienteArquivoController@delete');

        $router->post('/{pacienteId}/prontuarioSimples', 'Paciente\PacienteProntuarioController@storeProntuarioSimplesAvulso');
        $router->get('/{pacienteId}/prontuariosUnificados', 'Paciente\PacienteProntuarioController@getHistoricoUnificado');

        $router->post('/{pacienteId}/prontuarios/{prontuarioId}/observacao', 'Paciente\PacienteProntuarioController@storeObservacao');
        $router->get('/{pacienteId}/prontuarios/{prontuarioId}/observacao', 'Paciente\PacienteProntuarioController@getObservacoes');
        $router->get('/{pacienteId}/prontuarios/{prontuarioId}/observacao/{observacaoId}', 'Paciente\PacienteProntuarioController@getObservacoesById');

        $router->get('/{pacienteId}/convenios', 'Paciente\PacienteConvenioController@index');
        $router->post('/{pacienteId}/convenios', 'Paciente\PacienteConvenioController@store');
        $router->post('/{pacienteId}/convenios/{convenioId}/atualizar', 'Paciente\PacienteConvenioController@update');
        $router->delete('/{pacienteId}/convenios/{convenioId}', 'Paciente\PacienteConvenioController@delete');

        $router->get('/{pacienteId}/planoBeneficios', 'Paciente\PacientePlBeneficioController@index');
        $router->post('/{pacienteId}/planoBeneficios', 'Paciente\PacientePlBeneficioController@contrataPlano');
        $router->post('/{pacienteId}/planoBeneficios/alterar', 'Paciente\PacientePlBeneficioController@alterarPlano');
        $router->post('/{pacienteId}/planoBeneficios/cancelarAlteracaoPlano', 'Paciente\PacientePlBeneficioController@cancelarAlteracaoPlano');

        $router->get('/{pacienteId}/planoBeneficios/ativo', 'Paciente\PacientePlBeneficioController@planoAtivo');
        $router->get('/{pacienteId}/planoBeneficios/{plBeneficioContratadoId}/historicoPagamentos', 'Paciente\PacientePlBeneficioController@planoHistoricoPagamento');
        $router->delete('/{pacienteId}/planoBeneficios/{plBeneficioContratadoId}', 'Paciente\PacientePlBeneficioController@delete');
        $router->post('/{pacienteId}/codigo/enviarCodigo', 'Paciente\PacienteController@enviarCodigo');
        $router->post('/{pacienteId}/codigo/verificarCodigo', 'Paciente\PacienteController@verificarCodigo');
    });

    $router->group(['prefix' => 'doutores'], function ($router) {

        $router->get('/', 'Doutores\DoutoresController@index');
        $router->get('/testeGoogle', 'Doutores\DoutoresController@testeGoogle');
        $router->post('/{doutorId}/avaliacoes', 'Doutores\DoutoresController@storeAvaliacoes');

        $router->get('/{doutorId}/convenios', 'Doutores\DoutoresController@getConveniosDoutores');

        $router->get('/{doutorId}/fotos', 'Doutores\DoutoresFotoController@index');
        $router->post('/{doutorId}/fotos', 'Doutores\DoutoresFotoController@store');
        $router->delete('/{doutorId}/fotos/{fotoId}', 'Doutores\DoutoresFotoController@delete');
        $router->post('/{doutorId}/fotos/{fotoId}/edit', 'Doutores\DoutoresFotoController@update');
    });

    $router->group(['prefix' => 'convenios'], function ($router) {
        $router->get('/', 'ConvenioController@index');
    });

    $router->group(['prefix' => 'configuracoes'], function ($router) {
        $router->get('/documentosExigidos', 'Configuracoes\DocumentosExigidosController@index');
    });

    $router->group(['prefix' => 'planoBeneficio'], function ($router) {
        $router->post('/', 'PlanoBeneficioController@store');
        $router->get('/', 'PlanoBeneficioController@index');
        $router->get('/{idPlano}', 'PlanoBeneficioController@getById');
        $router->post('/ativo', 'PlanoBeneficioController@getPlBeneficioPacienteAtivo');
        $router->post('/{idPlano}/atualizar', 'PlanoBeneficioController@update');
        $router->delete('/{idPlano}', 'PlanoBeneficioController@delete');
    });

    $router->post('/agenda/atendimento/{consultaId}/iniciarAtendimento', 'AgendaController@iniciarAtendimento');
    $router->post('/agenda/atendimento/{consultaId}/salvarAtendimento', 'AgendaController@salvarAtendimento');
    $router->post('/agenda/bloqueioHorarios/{doutorId}', 'AgendaController@bloqueioRapidoAgenda');
    $router->post('/agenda/desbloqueioHorarios/{doutorId}', 'AgendaController@desbloqueioRapidoAgenda');
    $router->get('/agenda/verificaMudancaAgenda/{doutorId}', 'AgendaController@verificaMudancaAgenda');

    $router->get('/procedimentos/consultas/{consultaId}', 'ProcedimentoController@getByConsulta');
    $router->get('/procedimentos/doutores/{doutorId}', 'ProcedimentoController@getByDoutor');
    $router->get('/relatorios/agendamentos/', 'RelatorioController@getRelAgendamento');
    $router->get('/perfisUsuarios/', 'PerfisUsuariosController@index');
});

//Pacientes
$router->group(['middleware' => 'auth:clinicas_pacientes', 'prefix' => 'v1/clinicas', 'namespace' => 'ApiClinicas'], function ($router) {

    $router->get('/me', 'AuthController@me');
    $router->post('logout', 'AuthController@logout');
    $router->post('alterarSenhaPaciente', 'AuthController@alterarSenhaPaciente');
    //Especialides
    $router->group(['prefix' => 'especialidades'], function ($router) {

        $router->get('/', 'EspecialidadeController@index');
    });
    $router->get('/gruposAtendimento', 'GrupoAtendimentoController@index');
    $router->get('/idiomas', 'IdiomaClinicaController@index');
    $router->get('/formacoesDoutores', 'DoutorFormacaoController@index');

    //Agenda
    $router->group(['prefix' => 'agenda'], function ($router) {
        $router->get('/filaEspera/{doutorId}', 'AgendaController@filaEspera');
        $router->get('/calendarioPreenchido/{doutorId}', 'AgendaController@percentualCalPreenchido');
        $router->get('/calendarioDiasDisponibilidade/{doutorId}', 'AgendaController@calendarioDiasDisponibilidade');
        $router->get('/listaHorarios/{doutorId}', 'AgendaController@listaHorarios');
        $router->get('/resumoDiario/{doutorId}', 'AgendaController@resumoDiario');
    });

    //Administracao
    $router->group(['prefix' => 'administracao'], function ($router) {
        $router->get('/lgpdAutorizacoes', 'Administracao\LgpdAutorizacoesController@index');
        $router->get('/lgpdAutorizacoes/termosCondicoes', 'Administracao\LgpdAutorizacoesController@getTermosCondicoes');
    });
    //Consultas
    $router->group(['prefix' => 'consultas'], function ($router) {
        $router->post('/', 'ConsultaController@store');
        $router->post('/alterar/{consultaId}', 'ConsultaController@update');
        $router->get('/', 'ConsultaController@index');
        $router->post('/{consultaId}/confirmar', 'ConsultaController@confirmar'); //ok       
        $router->post('/{consultaId}/desmarcar', 'ConsultaController@desmarcar'); //ok   
        $router->post('/{consultaId}/alterarStatus', 'ConsultaController@alterarStatus'); //ok       
        $router->get('/{consultaId}', 'ConsultaController@getById');
        $router->delete('/{consultaId}', 'ConsultaController@delete');
    });
    $router->group(['prefix' => 'compromissos'], function ($router) {
        $router->get('/', 'CompromissoController@index');
        $router->get('/{compromissoId}', 'CompromissoController@getById');
        $router->put('/{compromissoId}', 'CompromissoController@update');
        $router->post('/', 'CompromissoController@store');
        $router->delete('/{compromissoId}', 'CompromissoController@delete');
        $router->post('/{compromissoId}/alterarStatus', 'CompromissoController@alterarStatus');
    });

    $router->group(['prefix' => 'pacientes'], function ($router) {

        $router->get('/{pacienteId}', 'Paciente\PacienteController@getById');
        $router->post('/{pacienteId}/alteraFotoPerfil', 'Paciente\PacienteController@alteraFotoPaciente');
        $router->post('/{pacienteId}/atualizar', 'Paciente\PacienteController@update');
        $router->get('/{pacienteId}/consultas/proximas', 'Paciente\PacienteController@proximasConsultas');
        $router->get('/{pacienteId}/consultas/historico', 'Paciente\PacienteController@getHistoricoConsulta');
        $router->get('/{pacienteId}/dependentes', 'Paciente\PacienteController@getDependentes');
        $router->get('/{pacienteId}/fotos', 'Paciente\PacienteFotoController@index');
        $router->post('/{pacienteId}/fotos', 'Paciente\PacienteFotoController@store');
        $router->delete('/{pacienteId}/fotos/{fotoId}', 'Paciente\PacienteFotoController@delete');
        $router->post('/{pacienteId}/fotos/{fotoId}/edit', 'Paciente\PacienteFotoController@update');

        $router->get('/{pacienteId}/arquivos', 'Paciente\PacienteArquivoController@index');
        $router->post('/{pacienteId}/arquivos', 'Paciente\PacienteArquivoController@store');
        $router->post('/{pacienteId}/arquivos/{arquivoId}/edit', 'Paciente\PacienteArquivoController@update');
        $router->delete('/{pacienteId}/arquivos/{arquivoId}', 'Paciente\PacienteArquivoController@delete');

        $router->post('/{pacienteId}/prontuarioSimples', 'Paciente\PacienteProntuarioController@storeProntuarioSimplesAvulso');
        $router->get('/{pacienteId}/prontuariosUnificados', 'Paciente\PacienteProntuarioController@getHistoricoUnificado');

        $router->post('/{pacienteId}/prontuarios/{prontuarioId}/observacao', 'Paciente\PacienteProntuarioController@storeObservacao');
        $router->get('/{pacienteId}/prontuarios/{prontuarioId}/observacao', 'Paciente\PacienteProntuarioController@getObservacoes');
        $router->get('/{pacienteId}/prontuarios/{prontuarioId}/observacao/{observacaoId}', 'Paciente\PacienteProntuarioController@getObservacoesById');

        $router->post('/{pacienteId}/favoritos', 'Paciente\PacienteFavoritosController@store');
        $router->get('/{pacienteId}/favoritos', 'Paciente\PacienteFavoritosController@index');
        $router->delete('/{pacienteId}/favoritos/{idFavorito}', 'Paciente\PacienteFavoritosController@delete');

        $router->get('/{pacienteId}/convenios', 'Paciente\PacienteConvenioController@index');
        $router->post('/{pacienteId}/convenios', 'Paciente\PacienteConvenioController@store');
        $router->post('/{pacienteId}/convenios/{convenioId}/atualizar', 'Paciente\PacienteConvenioController@update');
        $router->delete('/{pacienteId}/convenios/{convenioId}', 'Paciente\PacienteConvenioController@delete');

        $router->get('/{pacienteId}/planoBeneficios', 'Paciente\PacientePlBeneficioController@index');
        $router->post('/{pacienteId}/planoBeneficios', 'Paciente\PacientePlBeneficioController@contrataPlano');
        $router->post('/{pacienteId}/planoBeneficios/alterar', 'Paciente\PacientePlBeneficioController@alterarPlano');
        $router->post('/{pacienteId}/planoBeneficios/cancelarAlteracaoPlano', 'Paciente\PacientePlBeneficioController@cancelarAlteracaoPlano');

        $router->get('/{pacienteId}/planoBeneficios/ativo', 'Paciente\PacientePlBeneficioController@planoAtivo');
        $router->get('/{pacienteId}/planoBeneficios/{plBeneficioContratadoId}/historicoPagamentos', 'Paciente\PacientePlBeneficioController@planoHistoricoPagamento');
        $router->post('/{pacienteId}/codigo/enviarCodigo', 'Paciente\PacienteController@enviarCodigo');
        $router->post('/{pacienteId}/codigo/verificarCodigo', 'Paciente\PacienteController@verificarCodigo');
    });

    //Doutores
    $router->group(['prefix' => 'doutores'], function ($router) {
        $router->get('/', 'Doutores\DoutoresController@index');
        $router->get('/filtros', 'Doutores\DoutoresController@filtros');
        $router->get('/{doutorId}/convenios', 'Doutores\DoutoresController@getConveniosDoutores');
        $router->get('/{doutorId}/fotos', 'Doutores\DoutoresFotoController@index');
        $router->post('/{doutorId}/fotos', 'Doutores\DoutoresFotoController@store');
        $router->delete('/{doutorId}/fotos/{fotoId}', 'Doutores\DoutoresFotoController@delete');
        $router->post('/{doutorId}/fotos/{fotoId}/edit', 'Doutores\DoutoresFotoController@update');
    });

    $router->group(['prefix' => 'convenios'], function ($router) {
        $router->get('/', 'ConvenioController@index');
    });

    $router->group(['prefix' => 'configuracoes'], function ($router) {
        $router->get('/documentosExigidos', 'Configuracoes\DocumentosExigidosController@index');
    });
    $router->group(['prefix' => 'planoBeneficio'], function ($router) {
        $router->post('/', 'PlanoBeneficioController@store');
        $router->get('/', 'PlanoBeneficioController@index');
        $router->get('/{idPlano}', 'PlanoBeneficioController@getById');
        $router->post('/ativo', 'PlanoBeneficioController@getPlBeneficioPacienteAtivo');
        $router->delete('/{idPlano}', 'PlanoBeneficioController@delete');
        $router->post('/{idPlano}/atualizar', 'PlanoBeneficioController@update');
    });

    $router->post('/agenda/atendimento/{consultaId}/iniciarAtendimento', 'AgendaController@iniciarAtendimento');
    $router->post('/agenda/atendimento/{consultaId}/salvarAtendimento', 'AgendaController@salvarAtendimento');
    $router->post('/agenda/bloqueioHorarios/{doutorId}', 'AgendaController@bloqueioRapidoAgenda');
    $router->post('/agenda/desbloqueioHorarios/{doutorId}', 'AgendaController@desbloqueioRapidoAgenda');
    $router->get('/agenda/verificaMudancaAgenda/{doutorId}', 'AgendaController@verificaMudancaAgenda');
    $router->get('/agenda/alertas', 'AgendaController@alertas');

    $router->get('/procedimentos/consultas/{consultaId}', 'ProcedimentoController@getByConsulta');
    $router->get('/procedimentos/doutores/{doutorId}', 'ProcedimentoController@getByDoutor');
    $router->get('/relatorios/agendamentos/', 'RelatorioController@getRelAgendamento');
    $router->get('/perfisUsuarios/', 'PerfisUsuariosController@index');
});

//Api interna
$router->group(['prefix' => '/v1/clinicas'], function () use ($router) {
    $router->post('loginInterno', 'AuthControllerInterno@loginInterno');
});

$router->group(['middleware' => 'auth:interno_api', 'prefix' => 'v1/clinicas'], function ($router) {

    $router->get('/interno/me', 'AuthControllerInterno@me');

    $router->group(['namespace' => 'ApiClinicas'], function ($router) {

        $router->get('/idiomas', 'IdiomaClinicaController@index'); //ok
        $router->get('/gruposAtendimento', 'GrupoAtendimentoController@index'); //ok
        $router->get('/formacoesDoutores', 'DoutorFormacaoController@index'); //ok
        //Especialides
        $router->group(['prefix' => 'especialidades'], function ($router) {
            $router->get('/', 'EspecialidadeController@index'); //ok
        });

        //Agenda
        $router->group(['prefix' => 'agenda'], function ($router) {
            $router->get('/filaEspera/{doutorId}', 'AgendaController@filaEspera'); //ok
            $router->get('/calendarioPreenchido/{doutorId}', 'AgendaController@percentualCalPreenchido'); //ok
            $router->get('/calendarioDiasDisponibilidade/{doutorId}', 'AgendaController@calendarioDiasDisponibilidade');
            $router->get('/listaHorarios/{doutorId}', 'AgendaController@listaHorarios'); //ok
            $router->get('/resumoDiario/{doutorId}', 'AgendaController@resumoDiario'); //ok
        });

        //Administracao
        $router->group(['prefix' => 'administracao'], function ($router) {
            $router->get('/lgpdAutorizacoes', 'Administracao\LgpdAutorizacoesController@index');
            $router->get('/lgpdAutorizacoes/termosCondicoes', 'Administracao\LgpdAutorizacoesController@getTermosCondicoes');
        });
        //CONTINUAR DAQUI A ALTERAÇÂO DE USUARIO INTERNO
        //Consultas
        $router->group(['prefix' => 'consultas'], function ($router) {
            $router->get('/', 'ConsultaController@index'); //ok
            $router->post('/', 'ConsultaController@store'); //ok            
            $router->post('/alterar/{consultaId}', 'ConsultaController@update');
            $router->get('/{consultaId}', 'ConsultaController@getById'); //ok
            $router->delete('/{consultaId}', 'ConsultaController@delete');
        });

        $router->group(['prefix' => 'compromissos'], function ($router) {
            $router->get('/', 'CompromissoController@index');
            $router->get('/{compromissoId}', 'CompromissoController@getById');
            $router->put('/{compromissoId}', 'CompromissoController@update');
            $router->post('/', 'CompromissoController@store');
            $router->delete('/{compromissoId}', 'CompromissoController@delete');
            $router->post('/{compromissoId}/alterarStatus', 'CompromissoController@alterarStatus');
        });

        $router->group(['prefix' => 'financeiro'], function ($router) {

            $router->group(['prefix' => 'relatorios'], function ($router) {
                $router->get('/relMensalPdf', 'Financeiro\RelatorioController@relMensalPdf'); //ok
                $router->get('/relDiarioDoutor', 'Financeiro\RelatorioController@relDiarioDoutor'); //ok
                $router->get('/relDiarioDoutorCalendario', 'Financeiro\RelatorioController@relDiarioDoutorCalendario'); //ok
            });
            $router->group(['prefix' => 'formasPagamentos'], function ($router) {
                $router->get('/', 'Financeiro\FormaPagamentoController@getAll');
            });
            $router->group(['prefix' => 'recebimentos'], function ($router) {
                $router->post('/', 'Financeiro\RecebimentoController@store');
            });
        });

        $router->group(['prefix' => 'pacientes'], function ($router) {

            $router->get('/{pacienteId}', 'Paciente\PacienteController@getById');
            $router->post('/{pacienteId}/alteraFotoPerfil', 'Paciente\PacienteController@alteraFotoPaciente');
            $router->post('/{pacienteId}/atualizar', 'Paciente\PacienteController@update');
            $router->get('/{pacienteId}/consultas/proximas', 'Paciente\PacienteController@proximasConsultas');
            $router->get('/{pacienteId}/consultas/historico', 'Paciente\PacienteController@getHistoricoConsulta');
            $router->get('/{pacienteId}/dependentes', 'Paciente\PacienteController@getDependentes');

            $router->get('/{pacienteId}/fotos', 'Paciente\PacienteFotoController@index'); //ok
            $router->post('/{pacienteId}/fotos', 'Paciente\PacienteFotoController@store'); //ok
            $router->delete('/{pacienteId}/fotos/{fotoId}', 'Paciente\PacienteFotoController@delete'); //ok
            $router->post('/{pacienteId}/fotos/{fotoId}/edit', 'Paciente\PacienteFotoController@update'); //ok

            $router->get('/{pacienteId}/arquivos', 'Paciente\PacienteArquivoController@index'); //ok
            $router->post('/{pacienteId}/arquivos', 'Paciente\PacienteArquivoController@store'); //ok
            $router->post('/{pacienteId}/arquivos/{arquivoId}/edit', 'Paciente\PacienteArquivoController@update'); //ok
            $router->delete('/{pacienteId}/arquivos/{arquivoId}', 'Paciente\PacienteArquivoController@delete'); //ok

            $router->post('/{pacienteId}/prontuarioSimples', 'Paciente\PacienteProntuarioController@storeProntuarioSimplesAvulso'); //ok
            $router->get('/{pacienteId}/prontuariosUnificados', 'Paciente\PacienteProntuarioController@getHistoricoUnificado'); //ok

            $router->post('/{pacienteId}/prontuarios/{prontuarioId}/observacao', 'Paciente\PacienteProntuarioController@storeObservacao'); //ok
            $router->get('/{pacienteId}/prontuarios/{prontuarioId}/observacao', 'Paciente\PacienteProntuarioController@getObservacoes'); //ok
            $router->get('/{pacienteId}/prontuarios/{prontuarioId}/observacao/{observacaoId}', 'Paciente\PacienteProntuarioController@getObservacoesById'); //ok


            $router->post('/{pacienteId}/favoritos', 'Paciente\PacienteFavoritosController@store'); //ok
            $router->get('/{pacienteId}/favoritos', 'Paciente\PacienteFavoritosController@index'); //ok
            $router->delete('/{pacienteId}/favoritos/{idFavorito}', 'Paciente\PacienteFavoritosController@delete'); //ok

            $router->post('/login', 'Paciente\PacienteController@login'); //ok
            $router->post('/esqueciSenha', 'Paciente\PacienteController@esqueciSenha');
            $router->post('/{pacienteId}/alterarSenha', 'Paciente\PacienteController@alterarSenha');

            $router->post('/google/registrar', 'Paciente\PacienteController@googleRegistrar');
            $router->post('/google/login', 'Paciente\PacienteController@googleLogin');

            $router->post('/facebook/registrar', 'Paciente\PacienteController@facebookRegistrar');
            $router->post('/facebook/login', 'Paciente\PacienteController@facebookLogin');
        });

        $router->group(['prefix' => 'doutores'], function ($router) {

            $router->get('/', 'Doutores\DoutoresController@index'); //ok
            $router->get('/filtros', 'Doutores\DoutoresController@filtros'); //ok
            $router->post('/{doutorId}/avaliacoes', 'Doutores\DoutoresController@storeAvaliacoes'); //ok
        });

        $router->group(['prefix' => 'convenios'], function ($router) {
            $router->get('/', 'ConvenioController@index');
        });

        $router->group(['prefix' => 'configuracoes'], function ($router) {
            $router->get('/documentosExigidos', 'Configuracoes\DocumentosExigidosController@index');
        });
        $router->group(['prefix' => 'planoBeneficio'], function ($router) {
            $router->post('/', 'PlanoBeneficioController@store');
            $router->get('/', 'PlanoBeneficioController@index');
            $router->post('/ativo', 'PlanoBeneficioController@getPlBeneficioPacienteAtivo');
            $router->get('/{idPlano}', 'PlanoBeneficioController@getById');
            $router->delete('/{idPlano}', 'PlanoBeneficioController@delete');
            $router->post('/{idPlano}/atualizar', 'PlanoBeneficioController@update');
        });

        $router->post('/agenda/atendimento/{consultaId}/iniciarAtendimento', 'AgendaController@iniciarAtendimento');
        $router->post('/agenda/atendimento/{consultaId}/salvarAtendimento', 'AgendaController@salvarAtendimento');
        $router->post('/agenda/bloqueioHorarios/{doutorId}', 'AgendaController@bloqueioRapidoAgenda');
        $router->post('/agenda/desbloqueioHorarios/{doutorId}', 'AgendaController@desbloqueioRapidoAgenda');
        $router->get('/agenda/verificaMudancaAgenda/{doutorId}', 'AgendaController@verificaMudancaAgenda');
        $router->get('/agenda/alertas', 'AgendaController@alertas');

        $router->get('/procedimentos/consultas/{consultaId}', 'ProcedimentoController@getByConsulta'); //ok
        $router->get('/procedimentos/doutores/{doutorId}', 'ProcedimentoController@getByDoutor'); //ok


        $router->get('/relatorios/agendamentos/', 'RelatorioController@getRelAgendamento');
        $router->get('/relatorios/agendamentos2/', 'RelatorioController@getRelAgendamento2');
        $router->get('/perfisUsuarios/', 'PerfisUsuariosController@index');
        $router->group(['prefix' => 'empresas'], function ($router) {
            $router->get('/', 'EmpresaController@getAll');
        });
    });
});
