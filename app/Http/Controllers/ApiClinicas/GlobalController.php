<?php

namespace App\Http\Controllers\ApiClinicas;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiClinicas\Controller;
use App\Services\Clinicas\EspecialidadeService;
use App\Repositories\Clinicas\ConselhosProfissionaisRepository;
use App\Repositories\Clinicas\CboRepository;

class GlobalController extends Controller {

    private $especialidadeService;

    public function __construct(EspecialidadeService $espServ) {
        $this->especialidadeService = $espServ;
    }

    public function conselhosProfissionais(Request $request) {
    
        $ConselhosProfissionaisRepository = new ConselhosProfissionaisRepository;
        $result = $ConselhosProfissionaisRepository->getAll();
        return response()->json([
            'success' =>true,
            'data' =>$result,
        ]);
    }
    public function cbo(Request $request) {
    
        $CboRepository = new CboRepository;
        $dadosFiltro['search'] = ($request->has('search'))?$request->query('search'):null;
       
        $result = $CboRepository->getAll($dadosFiltro);
        return response()->json([
            'success' =>true,
            'data' =>$result,
        ]);
    }
  

}
