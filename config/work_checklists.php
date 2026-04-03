<?php

return [
    'templates' => [
        'obra_arranque' => [
            'name' => 'Checklist de arranque',
            'description' => 'Validacoes iniciais antes de iniciar os trabalhos no terreno.',
            'items' => [
                ['description' => 'Orcamento/contrato validado', 'is_required' => true],
                ['description' => 'Documentacao tecnica e projeto disponiveis', 'is_required' => true],
                ['description' => 'Plano de seguranca comunicado a equipa', 'is_required' => true],
                ['description' => 'Equipa atribuida e contacto do responsavel tecnico confirmado', 'is_required' => true],
                ['description' => 'Planeamento inicial de tarefas registado', 'is_required' => false],
                ['description' => 'Materiais iniciais verificados para arranque', 'is_required' => false],
            ],
        ],
        'obra_execucao' => [
            'name' => 'Checklist de execucao',
            'description' => 'Controlo operativo recorrente durante a execucao da obra.',
            'items' => [
                ['description' => 'Diario de obra atualizado com horas e resumo do dia', 'is_required' => true],
                ['description' => 'Materiais aplicados registados no diario', 'is_required' => true],
                ['description' => 'Ocorrencias e desvios registados', 'is_required' => false],
                ['description' => 'Ficheiros/fotos anexados quando aplicavel', 'is_required' => false],
                ['description' => 'Outros custos validados', 'is_required' => false],
            ],
        ],
        'obra_fecho' => [
            'name' => 'Checklist de fecho',
            'description' => 'Validacoes finais obrigatorias para concluir a obra.',
            'items' => [
                ['description' => 'Pendencias tecnicas resolvidas', 'is_required' => true],
                ['description' => 'Checklist de conformidade final validada', 'is_required' => true],
                ['description' => 'Entrega final ao cliente registada', 'is_required' => true],
                ['description' => 'Ficheiros finais (auto, fotos, certificados) arquivados', 'is_required' => false],
                ['description' => 'Resumo economico revisto', 'is_required' => false],
            ],
        ],
        'instalacao_eletrica_obra_nova' => [
            'name' => 'Checklist final - Instalacao eletrica (obra nova)',
            'description' => 'Checklist completa de verificacao final para instalacao eletrica em obra nova.',
            'items' => [
                ['description' => '1.1 Conferir se toda a instalacao esta concluida (sem pontos em aberto)', 'is_required' => true],
                ['description' => '1.2 Confirmar que nao existem cabos expostos ou mal isolados', 'is_required' => true],
                ['description' => '1.3 Verificar fixacao de tubos, caixas e aparelhagem', 'is_required' => true],
                ['description' => '1.4 Confirmar que nao ha danos visiveis (cortes, esmagamentos, etc.)', 'is_required' => true],

                ['description' => '2.1 Confirmar identificacao de todos os disjuntores (etiquetas claras)', 'is_required' => true],
                ['description' => '2.2 Confirmar aperto de todos os bornes (fase, neutro e terra)', 'is_required' => true],
                ['description' => '2.3 Verificar barramentos bem fixos', 'is_required' => true],
                ['description' => '2.4 Conferir organizacao dos cabos no quadro', 'is_required' => true],
                ['description' => '2.5 Testar funcionamento do diferencial (botao TEST)', 'is_required' => true],
                ['description' => '2.6 Confirmar seletividade (se aplicavel)', 'is_required' => false],

                ['description' => '3.1 Medir resistencia de terra (valor dentro do aceitavel)', 'is_required' => true],
                ['description' => '3.2 Verificar ligacao de todas as massas metalicas', 'is_required' => true],
                ['description' => '3.3 Confirmar continuidade do condutor de protecao (PE)', 'is_required' => true],
                ['description' => '3.4 Verificar ligacao ao eletrodo de terra', 'is_required' => true],

                ['description' => '4.1 Testar todos os pontos de luz', 'is_required' => true],
                ['description' => '4.2 Verificar comutadores e cruzamentos', 'is_required' => true],
                ['description' => '4.3 Confirmar polaridade correta (fase no interruptor)', 'is_required' => true],
                ['description' => '4.4 Testar temporizacoes (se existirem)', 'is_required' => false],

                ['description' => '5.1 Testar todas as tomadas (fase/neutro/terra)', 'is_required' => true],
                ['description' => '5.2 Verificar aperto dos fios nas tomadas', 'is_required' => true],
                ['description' => '5.3 Confirmar que as tomadas estao niveladas e fixas', 'is_required' => true],
                ['description' => '5.4 Testar tomadas especiais (forno, placa, maquinas, etc.)', 'is_required' => true],

                ['description' => '6.1 Ensaio de continuidade dos condutores', 'is_required' => true],
                ['description' => '6.2 Ensaio de resistencia de isolamento', 'is_required' => true],
                ['description' => '6.3 Verificacao de polaridade', 'is_required' => true],
                ['description' => '6.4 Teste de disparo do diferencial (tempo e corrente)', 'is_required' => true],
                ['description' => '6.5 Medicao da impedancia de loop (se aplicavel)', 'is_required' => false],

                ['description' => '7.1 Verificar protecao contra contactos diretos e indiretos', 'is_required' => true],
                ['description' => '7.2 Confirmar que nao ha partes acessiveis energizadas', 'is_required' => true],
                ['description' => '7.3 Conferir distancias de seguranca', 'is_required' => true],
                ['description' => '7.4 Verificar protecao mecanica dos cabos', 'is_required' => true],

                ['description' => '8.1 Testar videoporteiro/intercomunicador (se houver)', 'is_required' => false],
                ['description' => '8.2 Testar portoes automaticos (se houver)', 'is_required' => false],
                ['description' => '8.3 Testar climatizacao/AC (se houver)', 'is_required' => false],
                ['description' => '8.4 Testar termoacumulador/bomba de calor (se houver)', 'is_required' => false],
                ['description' => '8.5 Testar carregador de veiculo eletrico (se houver)', 'is_required' => false],
                ['description' => '8.6 Testar sistemas fotovoltaicos (se houver)', 'is_required' => false],

                ['description' => '9.1 Confirmar esquema eletrico atualizado (como executado)', 'is_required' => true],
                ['description' => '9.2 Confirmar identificacao do quadro', 'is_required' => true],
                ['description' => '9.3 Entregar manual/instrucoes ao cliente', 'is_required' => false],
                ['description' => '9.4 Emitir certificado/relatorio de ensaios (se aplicavel)', 'is_required' => false],
                ['description' => '9.5 Preparar para inspecao (se necessario)', 'is_required' => false],

                ['description' => '10.1 Limpeza final das caixas e do quadro', 'is_required' => true],
                ['description' => '10.2 Retirar restos de cabos e lixo', 'is_required' => true],
                ['description' => '10.3 Verificar estetica final da instalacao', 'is_required' => true],
                ['description' => '10.4 Tirar fotos finais da instalacao', 'is_required' => false],
            ],
        ],
    ],
];
