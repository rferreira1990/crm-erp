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
    ],
];
