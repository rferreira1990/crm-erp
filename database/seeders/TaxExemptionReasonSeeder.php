<?php

namespace Database\Seeders;

use App\Models\TaxExemptionReason;
use Illuminate\Database\Seeder;

class TaxExemptionReasonSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'code' => 'M01',
                'description' => 'Quantias pagas em nome e por conta do adquirente dos bens ou do destinatário dos serviços, registadas pelo sujeito passivo em contas de terceiros apropriadas.',
                'invoice_note' => 'Artigo 16.º, n.º 6 do CIVA',
                'legal_reference' => 'Artigo 16.º, n.º 6, alíneas a) a d) do CIVA',
            ],
            [
                'code' => 'M02',
                'description' => 'Vendas de mercadorias de valor superior a 1.000 €/fatura, efetuadas por um fornecedor a um exportador nacional, exportadas no mesmo estado.',
                'invoice_note' => 'Artigo 6.º do Decreto-Lei n.º 198/90, de 19 de junho',
                'legal_reference' => 'Artigo 6.º do Decreto-Lei n.º 198/90, de 19 de junho',
            ],
            [
                'code' => 'M04',
                'description' => 'Certo tipo de importações ou reimportações.',
                'invoice_note' => 'Isento artigo 13.º do CIVA',
                'legal_reference' => 'Artigo 13.º do CIVA',
            ],
            [
                'code' => 'M05',
                'description' => 'Exportações, operações assimiladas e transportes internacionais.',
                'invoice_note' => 'Isento artigo 14.º do CIVA',
                'legal_reference' => 'Artigo 14.º do CIVA',
            ],
            [
                'code' => 'M06',
                'description' => 'Operações relacionadas com regimes suspensivos.',
                'invoice_note' => 'Isento artigo 15.º do CIVA',
                'legal_reference' => 'Artigo 15.º do CIVA',
            ],
            [
                'code' => 'M07',
                'description' => 'Variadas atividades referentes à saúde, apoio social, artes e espetáculos, seguros, locação de espaços, lotarias e apostas devidamente autorizadas e outras.',
                'invoice_note' => 'Isento artigo 9.º do CIVA',
                'legal_reference' => 'Artigo 9.º do CIVA',
            ],
            [
                'code' => 'M09',
                'description' => 'Retalhistas enquadrados no regime especial aplicável.',
                'invoice_note' => 'IVA – não confere direito a dedução',
                'legal_reference' => 'Artigo 62.º alínea b) do CIVA',
            ],
            [
                'code' => 'M10',
                'description' => 'IVA – regime de isenção aplicável a pequenos retalhistas e situações previstas legalmente.',
                'invoice_note' => 'IVA – regime de isenção',
                'legal_reference' => 'Artigo 57.º do CIVA de acordo com o referido no Artigo 53.º',
            ],
            [
                'code' => 'M11',
                'description' => 'Produtores e revendedores de tabaco.',
                'invoice_note' => 'Regime particular do tabaco',
                'legal_reference' => 'Decreto-Lei n.º 346/85, de 23 de agosto',
            ],
            [
                'code' => 'M12',
                'description' => 'Regime da margem de lucro – Agências de Viagens.',
                'invoice_note' => 'Regime da margem de lucro – Agências de Viagens',
                'legal_reference' => 'Decreto-Lei n.º 221/85, de 3 de julho',
            ],
            [
                'code' => 'M13',
                'description' => 'Regime da margem de lucro – Bens em segunda mão.',
                'invoice_note' => 'Regime da margem de lucro – Bens em segunda mão',
                'legal_reference' => 'Decreto-Lei n.º 199/96, de 18 de outubro',
            ],
            [
                'code' => 'M14',
                'description' => 'Regime da margem de lucro – Objetos de arte.',
                'invoice_note' => 'Regime da margem de lucro – Objetos de arte',
                'legal_reference' => 'Decreto-Lei n.º 199/96, de 18 de outubro',
            ],
            [
                'code' => 'M15',
                'description' => 'Regime da margem de lucro – Objetos de coleção e antiguidades.',
                'invoice_note' => 'Regime da margem de lucro – Objetos de coleção e antiguidades',
                'legal_reference' => 'Decreto-Lei n.º 199/96, de 18 de outubro',
            ],
            [
                'code' => 'M16',
                'description' => 'Transmissões de bens para outro Estado membro com NIF válido no VIES, em condições previstas legalmente.',
                'invoice_note' => 'Isento artigo 14.º do RITI',
                'legal_reference' => 'Artigo 14.º do RITI',
            ],
            [
                'code' => 'M19',
                'description' => 'Outras isenções.',
                'invoice_note' => 'Outras isenções',
                'legal_reference' => 'Isenções temporárias determinadas em diploma próprio',
            ],
            [
                'code' => 'M20',
                'description' => 'IVA – regime forfetário aplicável a produtos agrícolas e serviços relacionados.',
                'invoice_note' => 'IVA – regime forfetário',
                'legal_reference' => 'Artigo 59.º-D n.º 2 do CIVA',
            ],
            [
                'code' => 'M21',
                'description' => 'Entregas efetuadas por revendedores por conta dos distribuidores.',
                'invoice_note' => 'IVA – não confere direito à dedução (ou expressão similar)',
                'legal_reference' => 'Artigo 72.º n.º 4 do CIVA',
            ],
            [
                'code' => 'M25',
                'description' => 'Entrega de mercadorias à consignação.',
                'invoice_note' => 'Mercadorias à consignação',
                'legal_reference' => 'Artigo 38.º n.º 1 alínea a) do CIVA',
            ],
            [
                'code' => 'M26',
                'description' => 'Aplicação transitória de isenção de IVA a certos produtos alimentares.',
                'invoice_note' => 'Isenção de IVA com direito à dedução no cabaz alimentar',
                'legal_reference' => 'Lei n.º 17/2023, de 14 de abril',
            ],
            [
                'code' => 'M30',
                'description' => 'IVA – autoliquidação.',
                'invoice_note' => 'IVA – autoliquidação',
                'legal_reference' => 'Artigo 2.º n.º 1 alínea i) do CIVA',
            ],
            [
                'code' => 'M31',
                'description' => 'IVA – autoliquidação em serviços de construção civil.',
                'invoice_note' => 'IVA – autoliquidação',
                'legal_reference' => 'Artigo 2.º n.º 1 alínea j) do CIVA',
            ],
            [
                'code' => 'M32',
                'description' => 'IVA – autoliquidação em prestações de serviços ligadas a direitos de emissão e reduções certificadas.',
                'invoice_note' => 'IVA – autoliquidação',
                'legal_reference' => 'Artigo 2.º n.º 1 alínea l) do CIVA',
            ],
            [
                'code' => 'M33',
                'description' => 'IVA – autoliquidação em cortiça, madeira, pinhas e pinhões com casca.',
                'invoice_note' => 'IVA – autoliquidação',
                'legal_reference' => 'Artigo 2.º n.º 1 alínea m) do CIVA',
            ],
            [
                'code' => 'M34',
                'description' => 'IVA – autoliquidação.',
                'invoice_note' => 'IVA – autoliquidação',
                'legal_reference' => 'Artigo 2.º n.º 1 alínea n) do CIVA',
            ],
            [
                'code' => 'M40',
                'description' => 'IVA – autoliquidação.',
                'invoice_note' => 'IVA – autoliquidação',
                'legal_reference' => 'Artigo 6.º n.º 6 alínea a) do CIVA, a contrário',
            ],
            [
                'code' => 'M41',
                'description' => 'Aquisição intracomunitária sujeita a imposto no Estado membro de chegada, em condições legalmente previstas.',
                'invoice_note' => 'IVA – autoliquidação',
                'legal_reference' => 'Artigo 8.º n.º 3 do RITI',
            ],
            [
                'code' => 'M42',
                'description' => 'IVA – autoliquidação.',
                'invoice_note' => 'IVA – autoliquidação',
                'legal_reference' => 'Decreto-Lei n.º 21/2007, de 29 de janeiro',
            ],
            [
                'code' => 'M43',
                'description' => 'IVA – autoliquidação.',
                'invoice_note' => 'IVA – autoliquidação',
                'legal_reference' => 'Decreto-Lei n.º 362/99, de 16 de setembro',
            ],
            [
                'code' => 'M44',
                'description' => 'A utilizar nas operações que não sejam localizadas em Portugal por força das regras de exceção constantes dos números 7 e seguintes do artigo 6.º do Código do IVA.',
                'invoice_note' => 'IVA – Regras específicas – artigo 6.º',
                'legal_reference' => 'Artigo 6.º do CIVA – Regras específicas',
            ],
            [
                'code' => 'M45',
                'description' => 'A utilizar nas operações localizadas noutro Estado Membro da União Europeia e que ali fiquem isentas de IVA ao abrigo do Regime Transfronteiriço de Isenção.',
                'invoice_note' => 'IVA – regime transfronteiriço de isenção',
                'legal_reference' => 'Artigo 58.º-A do CIVA',
            ],
            [
                'code' => 'M46',
                'description' => 'A utilizar pelo vendedor na emissão de faturas relativas a operações em que tenha aplicado a isenção na transmissão de bens a serem transportados na bagagem pessoal de viajantes sem domicílio ou estabelecimento na União Europeia.',
                'invoice_note' => 'IVA – e-TaxFree',
                'legal_reference' => 'Decreto-Lei n.º 19/2017, de 14 de fevereiro',
            ],
            [
                'code' => 'M99',
                'description' => 'Ver outras situações abrangidas por isenções nos artigos indicados.',
                'invoice_note' => 'Não sujeito ou não tributado',
                'legal_reference' => 'Outras situações de não liquidação do imposto',
            ],
        ];

        foreach ($rows as $row) {
            TaxExemptionReason::updateOrCreate(
                ['code' => $row['code']],
                [
                    'description' => $row['description'],
                    'invoice_note' => $row['invoice_note'],
                    'legal_reference' => $row['legal_reference'],
                    'is_active' => true,
                ]
            );
        }
    }
}
