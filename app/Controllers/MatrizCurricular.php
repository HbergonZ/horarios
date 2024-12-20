<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MatrizCurricularModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Exceptions\ReferenciaException;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class MatrizCurricular extends BaseController
{
    public function index()
    {
        $matrizModel = new MatrizCurricularModel();
        $data['matrizes'] = $matrizModel->orderBy('nome', 'asc')->findAll();
        $data['content'] = view('sys/lista-matriz', $data);
        return view('dashboard', $data);
    }

    public function salvar()
    {
        $matrizModel = new MatrizCurricularModel();

        //coloca todos os dados do formulario no vetor dadosPost
        $dadosPost = $this->request->getPost();
        $dadosLimpos['nome'] = strip_tags($dadosPost['nome']);

        
        if ($matrizModel->insert($dadosLimpos)) {
            
            session()->setFlashdata('sucesso', 'Matriz cadastrado com sucesso.');
            return redirect()->to(base_url('/sys/matriz'));
        } else {
            $data['erros'] = $matrizModel->errors(); //o(s) erro(s)
            return redirect()->to(base_url('/sys/matriz'))->with('erros', $data['erros'])->withInput(); //retora com os erros e os inputs
        }
    }

    public function atualizar(){

        $dadosPost = $this->request->getPost();

        $dadosLimpos['id'] = strip_tags($dadosPost['id']);        
        $dadosLimpos['nome'] = strip_tags($dadosPost['nome']);

        $matrizModel = new MatrizCurricularModel();
        if($matrizModel->save($dadosLimpos)){
            session()->setFlashdata('sucesso', 'Matriz atualizado com sucesso.');
            return redirect()->to(base_url('/sys/matriz')); // Redireciona para a página de listagem
        } else {
            $data['erros'] = $matrizModel->errors(); //o(s) erro(s)
            return redirect()->to(base_url('/sys/matriz'))->with('erros', $data['erros']); //retora com os erros
        }

    }

    public function deletar(){
        
        $dadosPost = $this->request->getPost();
        $id = strip_tags($dadosPost['id']);

        $matrizModel = new MatrizCurricularModel();
        try {
            
            if ($matrizModel->delete($id)) {
                session()->setFlashdata('sucesso', 'Matriz excluído com sucesso.');
                return redirect()->to(base_url('/sys/matriz'));
            } else {
                return redirect()->to(base_url('/sys/matriz'))->with('erro', 'Falha ao deletar matriz');
            }
        } catch (ReferenciaException $e) {
            return redirect()->to(base_url('/sys/matriz'))->with('erros', ['erro' => $e->getMessage()]);
        }
    }

    public function importar() {

        $file = $this->request->getFile('arquivo');

        if (!$file->isValid()) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setBody('Erro: Arquivo não enviado.');
        }

        $extension = $file->getClientExtension();
        if (!in_array($extension, ['xls', 'xlsx'])) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNSUPPORTED_MEDIA_TYPE)
                ->setBody('Erro: Formato de arquivo não suportado. Apenas XLSX ou XLS');
        }

        $reader = $extension === 'xlsx' ? new Xlsx() : new Xls();

        try {
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setBody('Erro ao carregar o arquivo: ' . $e->getMessage());
        }

        $sheet = $spreadsheet->getActiveSheet();
        $dataRows = [];

        $matrizModel = new MatrizCurricularModel();
        $data['matrizesExistentes'] = [];

        foreach($matrizModel->getMatrizesNome() as $k)
        {
            array_push($data['matrizesExistentes'],$k['nome']);
        }

        // Lê os dados da planilha e captura Nome e E-mail
        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            $dataRows[] = [
                'nome' => $rowData[1] ?? null
            ];
        }

        // Remove cabeçalho
        array_shift($dataRows);

        // Exibe os dados lidos na view
        $data['matrizes'] = $dataRows;
        $data['content'] = view('sys/importar-matriz-form', $data);
        return view('dashboard', $data);
    }

    public function processarImportacao() {

        $selecionados = $this->request->getPost('selecionados');

        if (empty($selecionados)) {
            session()->setFlashdata('erro', 'Nenhum registro selecionado para importação.');
            return redirect()->to(base_url('/sys/matriz'));
        }

        $matrizModel = new MatrizCurricularModel();
        $insertedCount = 0;

        foreach ($selecionados as $registroJson) {
            $registro = json_decode($registroJson, true);

            if (!empty($registro['nome'])) {
                $matrizModel->insert($registro);
                $insertedCount++;
            }
        }

        session()->setFlashdata('sucesso', "{$insertedCount} registros importados com sucesso!");
        return redirect()->to(base_url('/sys/matriz'));
    }
}
