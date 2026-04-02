<?php

return [
    'templates' => [
        'obra_arranque' => [
            'name' => 'Checklist de arranque',
            'description' => 'Verificacoes iniciais antes do arranque da obra.',
            'items' => [
                ['description' => 'Documentacao tecnica validada', 'is_required' => true],
                ['description' => 'Equipa atribuida e informada', 'is_required' => true],
                ['description' => 'Plano de seguranca confirmado', 'is_required' => true],
                ['description' => 'Materiais iniciais disponiveis', 'is_required' => false],
            ],
        ],
        'obra_execucao' => [
            'name' => 'Checklist de execucao',
            'description' => 'Controlo operativo durante a execucao.',
            'items' => [
                ['description' => 'Registo diario atualizado', 'is_required' => true],
                ['description' => 'Ocorrencias analisadas', 'is_required' => false],
                ['description' => 'Ficheiros/fotos relevantes anexados', 'is_required' => false],
                ['description' => 'Custos do dia validados', 'is_required' => false],
            ],
        ],
        'obra_fecho' => [
            'name' => 'Checklist de fecho',
            'description' => 'Validacoes finais para concluir a obra.',
            'items' => [
                ['description' => 'Todos os itens obrigatorios do cliente validados', 'is_required' => true],
                ['description' => 'Pendencias tecnicas resolvidas', 'is_required' => true],
                ['description' => 'Entrega final registada', 'is_required' => true],
                ['description' => 'Documentacao final arquivada', 'is_required' => false],
            ],
        ],
    ],
];
