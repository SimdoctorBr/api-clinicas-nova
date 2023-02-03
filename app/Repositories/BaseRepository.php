<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * Description of BaseRepository
 *
 * @author ander
 */
class BaseRepository {

    protected $ENC_CODE = "19fa61d75522a4669b44e39c1d2e1726c530232130d407f89afee0964997f7a73e83be698b288febcf88e3e03c4f0757ea8964e59b63d93708b138cc42a66eb3";

    public function connGerenciamento() {

        return DB::connection('gerenciamento');
    }

    public function connClinicas() {
        return DB::connection('clinicas');
    }

    public function paginacao($camposSql, $from, $tipoConexao, $page, $perPage, $debug = false, $orderBy = null) {

        $Conexao = null;
        switch ($tipoConexao) {
            case 'clinicas' :$Conexao = $this->connClinicas();
                break;
            case 'gerenciamento' :$Conexao = $this->connGerenciamento();
                break;
        }
        if (!empty($Conexao)) {



            if ($debug) {
                var_dump("SELECT " . $camposSql . " " . $from);
                exit;
            } else {
                $qrPagTotalReg = $Conexao->select("SELECT COUNT(*) as total  " . $from);

                $totalRegistro = (count($qrPagTotalReg) > 0) ? $qrPagTotalReg[0]->total : 0;
                $pageSql = $page - 1;
                $inicioSql = $pageSql * $perPage;
                $totalPages = (int) ceil($totalRegistro / $perPage);
                $orderBy = (!empty($orderBy)) ? " ORDER BY $orderBy" : '';
                $qrPaginada = $Conexao->select("SELECT " . $camposSql . " " . $from . ' ' . $orderBy . " LIMIT $inicioSql,$perPage");

                $result = array(
                    'totalResults' => $totalRegistro,
                    'totalPages' => $totalPages,
                    'page' => $page,
                    'perPage' => $perPage,
                    'results' => $qrPaginada
                );

                return $result;
            }
        }
    }

    /**
     * 
     * @param type $table
     * @param array $dados
     * @param type $camposEncriptados
     * @param type $tipoConexao
     * @return type
     */
    protected function insertDB($table, Array $dados, $camposEncriptados = null, $tipoConexao) {

        $Conexao = null;
        switch ($tipoConexao) {
            case 'clinicas' :$Conexao = $this->connClinicas();
                break;
            case 'gerenciamento' :$Conexao = $this->connGerenciamento();
                break;
        }

        $prepares = array();
        foreach ($dados as $chave => $valor) {
            $prepares[] = '?';
            $campos[] = $chave;
            $values[] = (is_array($camposEncriptados) and in_array($chave, $camposEncriptados)) ? "AES_ENCRYPT('" . trim(addslashes($valor)) . "','$this->ENC_CODE')" : " '" . trim(addslashes($valor)) . "'";
        }
        $campos = implode(',', $campos);
        $values = implode(',', $values);

        $qr = $Conexao->insert("INSERT INTO $table ($campos) VALUES($values)");
        return $inserted = $Conexao->getPdo()->lastInsertId();
    }

    protected function updateDB($table, Array $dados, $where, $camposEncriptados = null, $tipoConexao) {

        $Conexao = null;
        switch ($tipoConexao) {
            case 'clinicas' :$Conexao = $this->connClinicas();
                break;
            case 'gerenciamento' :$Conexao = $this->connGerenciamento();
                break;
        }

        $prepares = array();
        foreach ($dados as $chave => $valor) {
            $prepares[] = '?';

            $valor = (is_array($camposEncriptados) and in_array($chave, $camposEncriptados)) ? "AES_ENCRYPT('" . trim(addslashes($valor)) . "','$this->ENC_CODE')" : " '" . trim(addslashes($valor)) . "'";

            $camposSet[] = $chave . ' = ' . $valor;
        }
        $camposSet = implode(',', $camposSet);

        if (empty($where)) {
            dd('where está vazio!!');
        }

//        dd("UPDATE $table SET $camposSet WHERE $where");
        $qr = $Conexao->update("UPDATE $table SET $camposSet WHERE $where");
        return $qr;
    }

}
