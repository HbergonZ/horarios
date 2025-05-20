<div class="page-header">
  <h3 class="page-title">RELATÓRIOS</h3>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= base_url('/sys/home') ?>">Início</a></li>
      <li class="breadcrumb-item active" aria-current="page">Relatórios</li>
    </ol>
  </nav>
</div>

<div class="row">
  <!-- Coluna dos Filtros (esquerda) -->
  <div class="col-md-5">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Filtros</h4>

        <div class="form-group">
          <label for="tipoRelatorio">Tipo de Relatório</label>
          <select class="form-control select2-single" id="tipoRelatorio" name="tipoRelatorio">
            <option value="">Selecione um tipo</option>
            <option value="curso">Relatório por Curso</option>
            <option value="professor">Relatório por Professor</option>
            <option value="ambiente">Relatório por Ambiente</option>
          </select>
        </div>

        <div id="filtrosDinamicos" class="mt-3">
          <div class="alert alert-info mb-0">
            Selecione um tipo de relatório para exibir os filtros correspondentes
          </div>
        </div>

        <div class="form-group d-flex justify-content-end mt-3">
          <button type="button" id="btnLimpar" class="btn btn-secondary me-2">
            <i class="mdi mdi-filter-remove me-1"></i>Limpar Filtros
          </button>
          <button type="button" id="btnGerarVisualizacao" class="btn btn-primary">
            <i class="mdi mdi-eye-outline me-1"></i>Gerar Visualização
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Coluna dos Resultados-->
  <div class="col-md-7">
    <div class="card" id="resultadosContainer" style="display: none;">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="card-title">Resultados</h4>
          <button type="button" id="btnExportar" class="btn btn-success">
            <i class="mdi mdi-file-export me-1"></i>Exportar PDF
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-striped" id="tabelaResultados">
            <thead>
              <tr>
                <th>Curso</th>
                <th>Turma</th>
                <th>Disciplina</th>
                <th>Professor</th>
                <th>Ambiente</th>
                <th>Dia</th>
                <th>Hora Início</th>
                <th>Hora Fim</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div style="display:none;">
  <!-- Templates modificados para mostrar selects um abaixo do outro -->
  <div id="templateCurso">
    <div class="form-group mt-3">
      <div class="form-check d-flex align-items-center mb-2">
        <input class="form-check-input mt-0" type="checkbox" id="checkTodosCursos">
        <label class="form-check-label" for="checkTodosCursos">Todos os Cursos</label>
      </div>
      <select class="form-control select2-multiple" multiple id="filtroCurso" name="cursos[]">
        <?php foreach ($cursos as $curso): ?>
          <option value="<?= $curso['id'] ?>"><?= $curso['nome'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group mt-3">
      <div class="form-check d-flex align-items-center mb-2">
        <input type="checkbox" class="form-check-input mt-0" id="checkTodasTurmas" disabled>
        <label class="form-check-label" for="checkTodasTurmas">Todas as Turmas</label>
      </div>
      <select class="form-control select2-multiple" multiple id="filtroTurma" name="turmas[]" disabled>
        <option disabled>Selecione um curso primeiro</option>
      </select>
    </div>
  </div>

  <div id="templateProfessor">
    <div class="form-group mt-3">
      <div class="form-check d-flex align-items-center mb-2">
        <input type="checkbox" class="form-check-input mt-0" id="checkTodosProfessores">
        <label class="form-check-label" for="checkTodosProfessores">Todos os Professores</label>
      </div>
      <select class="form-control select2-multiple" multiple id="filtroProfessor" name="professores[]">
        <?php foreach ($professores as $professor): ?>
          <option value="<?= $professor['id'] ?>"><?= $professor['nome'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div id="templateAmbiente">
    <div class="form-group mt-3">
      <div class="form-check d-flex align-items-center mb-2">
        <input type="checkbox" class="form-check-input mt-0" id="checkTodosAmbientes">
        <label class="form-check-label" for="checkTodosAmbientes">Todos os Ambientes</label>
      </div>
      <select class="form-control select2-multiple" multiple id="filtroAmbiente" name="ambientes[]">
        <?php foreach ($ambientes as $ambiente): ?>
          <option value="<?= $ambiente['id'] ?>"><?= $ambiente['nome'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';

    $.ajaxSetup({
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        [csrfName]: csrfHash
      }
    });

    $(".select2-single").select2({
      placeholder: "Selecione uma opção",
      allowClear: true,
      width: '100%',
    });

    // Função para lidar com checkboxes "Todos"
    function handleTodosCheckbox(checkboxId, selectId, dependentCheckboxId = null, dependentSelectId = null) {
      $(document).on('change', checkboxId, function() {
        var isChecked = $(this).is(':checked');
        var select = $(selectId);

        // Desabilita/habilita o select correspondente
        select.prop('disabled', isChecked);

        // Limpa a seleção se marcado
        if (isChecked) {
          select.val(null).trigger('change');
        }

        // Se houver um dependente (como turmas dependem de cursos)
        if (dependentCheckboxId && dependentSelectId) {
          $(dependentCheckboxId).prop('disabled', !isChecked);
          $(dependentSelectId).prop('disabled', !isChecked);

          if (!isChecked) {
            $(dependentCheckboxId).prop('checked', false);
            $(dependentSelectId).val(null).trigger('change');
          }
        }
      });
    }

    $('#tipoRelatorio').on('change', function() {
      var tipo = $(this).val();
      var filtrosContainer = $('#filtrosDinamicos');

      filtrosContainer.empty();

      switch (tipo) {
        case 'curso':
          filtrosContainer.html($('#templateCurso').html());

          // Configura os eventos para os checkboxes de cursos e turmas
          setTimeout(function() {
            filtrosContainer.find('.select2-multiple').select2({
              placeholder: "Selecione uma ou mais opções",
              allowClear: true,
              width: '100%'
            });

            // Configura checkbox "Todos os Cursos"
            handleTodosCheckbox('#checkTodosCursos', '#filtroCurso', '#checkTodasTurmas', '#filtroTurma');

            // Configura checkbox "Todas as Turmas"
            handleTodosCheckbox('#checkTodasTurmas', '#filtroTurma');

            // Evento para carregar turmas quando cursos são selecionados
            filtrosContainer.find('#filtroCurso').on('change', function() {
              var cursos = $(this).val();
              var turmaSelect = $('#filtroTurma');
              var checkTodasTurmas = $('#checkTodasTurmas');

              if (cursos && cursos.length > 0) {
                turmaSelect.prop('disabled', false);
                checkTodasTurmas.prop('disabled', false);
                carregarTurmasPorCurso(cursos);
              } else {
                turmaSelect.prop('disabled', true);
                checkTodasTurmas.prop('disabled', true).prop('checked', false);
                turmaSelect.val(null).trigger('change');
              }
            });
          }, 50);
          break;

        case 'professor':
          filtrosContainer.html($('#templateProfessor').html());

          setTimeout(function() {
            filtrosContainer.find('.select2-multiple').select2({
              placeholder: "Selecione uma ou mais opções",
              allowClear: true,
              width: '100%'
            });

            // Configura checkbox "Todos os Professores"
            handleTodosCheckbox('#checkTodosProfessores', '#filtroProfessor');
          }, 50);
          break;

        case 'ambiente':
          filtrosContainer.html($('#templateAmbiente').html());

          setTimeout(function() {
            filtrosContainer.find('.select2-multiple').select2({
              placeholder: "Selecione uma ou mais opções",
              allowClear: true,
              width: '100%'
            });

            // Configura checkbox "Todos os Ambientes"
            handleTodosCheckbox('#checkTodosAmbientes', '#filtroAmbiente');
          }, 50);
          break;

        default:
          filtrosContainer.html('<div class="alert alert-info mb-0">Selecione um tipo de relatório para exibir os filtros correspondentes</div>');
          return;
      }
    });

    function carregarTurmasPorCurso(cursos) {
      var turmaSelect = $('#filtroTurma');
      turmaSelect.empty().append('<option value="">Carregando...</option>').trigger('change');

      $.ajax({
        url: '<?= base_url('sys/relatorios/getTurmasByCurso') ?>',
        type: 'POST',
        data: {
          cursos: cursos,
          [csrfName]: csrfHash
        },
        dataType: 'json',
        success: function(response) {
          turmaSelect.empty();
          if (response && response.length > 0) {
            $.each(response, function(index, item) {
              turmaSelect.append(new Option(item.text, item.id));
            });
          }
          turmaSelect.trigger('change');
        },
        error: function(xhr, status, error) {
          console.error('Erro ao carregar turmas:', error);
          turmaSelect.empty().append('<option disabled>Erro ao carregar turmas</option>').trigger('change');
        }
      });
    }

    $('#btnGerarVisualizacao').on('click', function() {
      var tipo = $('#tipoRelatorio').val();

      if (!tipo) {
        $.toast({
          heading: 'Atenção',
          text: 'Selecione um tipo de relatório primeiro',
          showHideTransition: 'slide',
          icon: 'warning',
          loaderBg: '#f96868',
          position: 'top-center'
        });
        return;
      }

      var dados = {
        tipo: tipo,
        [csrfName]: csrfHash
      };

      switch (tipo) {
        case 'curso':
          if ($('#checkTodosCursos').is(':checked')) {
            // Não envia filtro de cursos (busca todos)
          } else {
            dados.cursos = $('#filtroCurso').val();
          }

          if ($('#checkTodasTurmas').is(':checked')) {
            // Não envia filtro de turmas (busca todas)
          } else {
            dados.turmas = $('#filtroTurma').val();
          }
          break;

        case 'professor':
          if ($('#checkTodosProfessores').is(':checked')) {
            // Não envia filtro de professores (busca todos)
          } else {
            dados.professores = $('#filtroProfessor').val();
          }
          break;

        case 'ambiente':
          if ($('#checkTodosAmbientes').is(':checked')) {
            // Não envia filtro de ambientes (busca todos)
          } else {
            dados.ambientes = $('#filtroAmbiente').val();
          }
          break;
      }

      var filtroPreenchido = false;

      // Verifica se pelo menos um filtro está preenchido ou se "Todos" está marcado
      if ((dados.cursos && dados.cursos.length > 0) ||
        (dados.turmas && dados.turmas.length > 0) ||
        (dados.professores && dados.professores.length > 0) ||
        (dados.ambientes && dados.ambientes.length > 0) ||
        $('#checkTodosCursos').is(':checked') ||
        $('#checkTodasTurmas').is(':checked') ||
        $('#checkTodosProfessores').is(':checked') ||
        $('#checkTodosAmbientes').is(':checked')) {
        filtroPreenchido = true;
      }

      if (!filtroPreenchido) {
        $.toast({
          heading: 'Atenção',
          text: 'Selecione pelo menos um filtro para gerar o relatório',
          showHideTransition: 'slide',
          icon: 'warning',
          loaderBg: '#f96868',
          position: 'top-center'
        });
        return;
      }

      var loading = $.toast({
        heading: 'Carregando dados...',
        text: 'Por favor aguarde',
        showHideTransition: 'fade',
        icon: 'info',
        hideAfter: false,
        position: 'top-center',
        bgColor: '#191c24'
      });

      $.ajax({
        url: '<?= base_url('sys/relatorios/filtrar') ?>',
        type: 'POST',
        data: dados,
        dataType: 'json',
        success: function(response) {
          $.toast().reset('all');

          if (response.success && response.data && response.data.length > 0) {
            var tbody = $('#tabelaResultados tbody');
            tbody.empty();

            $.each(response.data, function(index, item) {
              var row = $('<tr>');
              row.append($('<td>').text(item.curso || ''));
              row.append($('<td>').text(item.turma || ''));
              row.append($('<td>').text(item.disciplina || ''));
              row.append($('<td>').text(item.professor || ''));
              row.append($('<td>').text(item.ambiente || ''));
              row.append($('<td>').text(getDiaSemana(item.dia_semana)));
              row.append($('<td>').text(item.hora_inicio || ''));
              row.append($('<td>').text(item.hora_fim || ''));

              tbody.append(row);
            });

            $('#resultadosContainer').show();
          } else {
            $.toast({
              heading: 'Nenhum resultado',
              text: 'Não foram encontrados registros com os filtros selecionados',
              showHideTransition: 'slide',
              icon: 'info',
              loaderBg: '#46c35f',
              position: 'top-center'
            });
            $('#resultadosContainer').hide();
          }
        },
        error: function(xhr, status, error) {
          $.toast().reset('all');

          $.toast({
            heading: 'Erro',
            text: 'Ocorreu um erro ao carregar os dados. Por favor, tente novamente.',
            showHideTransition: 'slide',
            icon: 'error',
            loaderBg: '#f96868',
            position: 'top-center'
          });
          console.error('Erro ao carregar dados:', error);
        }
      });
    });

    function getDiaSemana(dia) {
      var dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
      return dias[dia] || dia;
    }

    $('#btnExportar').on('click', function() {
      alert("🚧 ATENÇÃO 🚧\n\nÁrea em construção!\n\nNossos programadores estão trabalhando duro (ou pelo menos é o que dizem)...");
    });

    $('#btnLimpar').on('click', function() {
      // Limpa todos os selects e checkboxes
      $('.select2-single, .select2-multiple').val(null).trigger('change');
      $('.form-check-input').prop('checked', false);
      $('.select2-multiple').prop('disabled', false);
      $('#checkTodasTurmas').prop('disabled', true);
      $('#tipoRelatorio').val('').trigger('change');
      $('#filtrosDinamicos').html('<div class="alert alert-info mb-0">Selecione um tipo de relatório para exibir os filtros correspondentes</div>');
      $('#resultadosContainer').hide();
    });
  });
</script>