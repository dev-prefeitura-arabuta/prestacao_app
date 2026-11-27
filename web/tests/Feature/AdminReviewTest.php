<?php

namespace Tests\Feature;

use App\Models\Accountability;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Services\ItemReviewService;
use App\Services\OpenWaService;
use App\Services\SmtpMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ferramentas do administrador: aviso de rejeição, motivos prontos, confirmação em lote,
 * duplicar prestação, fila "Para avaliar" e pendências por pessoa (Web e API mobile).
 */
class AdminReviewTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array<string, string>> */
    public array $mails = [];

    /** @var array<int, array<string, string>> */
    public array $whatsapps = [];

    public bool $channelsFail = false;

    protected function setUp(): void
    {
        parent::setUp();

        $test = $this;
        $this->app->instance(SmtpMailService::class, new class($test) extends SmtpMailService {
            public function __construct(private $test)
            {
            }

            public function send(...$args): array
            {
                $this->test->mails[] = ['to' => $args[0], 'subject' => $args[2], 'text' => (string) ($args[4] ?? '')];

                return $this->test->channelsFail ? ['success' => false, 'error' => 'SMTP off'] : ['success' => true, 'error' => null];
            }
        });
        $this->app->instance(OpenWaService::class, new class($test) extends OpenWaService {
            public function __construct(private $test)
            {
            }

            public function sendText(string $phone, string $text): array
            {
                $this->test->whatsapps[] = ['to' => $phone, 'text' => $text];

                return $this->test->channelsFail ? ['success' => false, 'error' => 'WA off'] : ['success' => true];
            }
        });
    }

    private function statusOf(Accountability $acc, int $item): ?string
    {
        return AccountabilityItemStatus::where('accountability_id', $acc->id)->where('item_number', $item)->value('status');
    }

    private function ajax(): array
    {
        return ['X-Requested-With' => 'XMLHttpRequest'];
    }

    // ---------- Aviso de rejeição ----------

    public function test_rejeicao_avisa_quem_enviou_por_email_e_whatsapp(): void
    {
        $acc   = $this->makeAccountability([1], ['title' => 'Prestacao Marco']);
        $user  = $this->makeUser('user', [1], ['whatsapp' => '(11) 98765-4321']);
        $admin = $this->makeUser('admin');
        $this->makeAttachment($acc, 1, $user);
        $this->makeAttachment($acc, 1, $admin, 'do_admin.pdf');
        $this->loginAs($admin);

        $this->postJson('/accountability/' . $acc->id . '/item/1/status', ['status' => 'rejeitado', 'rejection_reason' => 'Falta assinatura.'], $this->ajax())
            ->assertOk()
            ->assertJsonPath('notified', 1);

        $this->assertCount(1, $this->mails, 'Somente o usuário (não o admin) deve ser avisado');
        $this->assertSame($user->email, $this->mails[0]['to']);
        $this->assertStringContainsString('Falta assinatura.', $this->mails[0]['text']);
        $this->assertStringContainsString('Prestacao Marco', $this->mails[0]['text']);

        $this->assertCount(1, $this->whatsapps);
        $this->assertSame('5511987654321', $this->whatsapps[0]['to']);
    }

    public function test_falha_no_aviso_nao_impede_a_rejeicao(): void
    {
        $this->channelsFail = true;
        $acc  = $this->makeAccountability([1]);
        $user = $this->makeUser('user', [1], ['whatsapp' => '(11) 98765-4321']);
        $this->makeAttachment($acc, 1, $user);
        $this->loginAs($this->makeUser('admin'));

        $this->postJson('/accountability/' . $acc->id . '/item/1/status', ['status' => 'rejeitado', 'rejection_reason' => 'Ilegivel'], $this->ajax())
            ->assertOk()
            ->assertJsonPath('notified', 0);

        $this->assertSame('rejeitado', $this->statusOf($acc, 1));
    }

    public function test_confirmar_nao_envia_aviso(): void
    {
        $acc  = $this->makeAccountability([1]);
        $user = $this->makeUser('user', [1], ['whatsapp' => '(11) 98765-4321']);
        $this->makeAttachment($acc, 1, $user);
        $this->loginAs($this->makeUser('admin'));

        $this->postJson('/accountability/' . $acc->id . '/item/1/status', ['status' => 'confirmado'], $this->ajax())->assertOk();

        $this->assertSame([], $this->mails);
        $this->assertSame([], $this->whatsapps);
    }

    public function test_item_nao_habilitado_e_recusado(): void
    {
        $acc  = $this->makeAccountability([1]);
        $user = $this->makeUser('user', [1, 2]);
        $this->makeAttachment($acc, 2, $user);
        $this->loginAs($this->makeUser('admin'));

        $this->postJson('/accountability/' . $acc->id . '/item/2/status', ['status' => 'confirmado'], $this->ajax())->assertStatus(422);
        $this->assertNull($this->statusOf($acc, 2));
    }

    // ---------- Motivos prontos ----------

    public function test_motivos_prontos_aparecem_na_tela_do_admin(): void
    {
        $acc  = $this->makeAccountability([1]);
        $user = $this->makeUser('user', [1]);
        $this->makeAttachment($acc, 1, $user);
        $this->loginAs($this->makeUser('admin'));

        $this->get('/accountability/' . $acc->id)
            ->assertOk()
            ->assertSee('js-reject-preset', false)
            ->assertSee(rtrim(ItemReviewService::REJECTION_PRESETS[0], '.'));
    }

    // ---------- Confirmação em lote ----------

    public function test_confirma_todos_os_itens_enviados_de_uma_vez(): void
    {
        $acc  = $this->makeAccountability([1, 2, 3]);
        $user = $this->makeUser('user', [1, 2, 3]);
        foreach ([1, 2, 3] as $n) {
            $this->makeAttachment($acc, $n, $user, "doc{$n}.pdf");
        }
        $this->setItemStatus($acc, 1, 'enviado');
        $this->setItemStatus($acc, 2, 'enviado');
        $this->setItemStatus($acc, 3, 'rejeitado');
        $this->loginAs($this->makeUser('admin'));

        $this->get('/accountability/' . $acc->id)->assertSee('Confirmar 2 enviado(s)');

        $this->post('/accountability/' . $acc->id . '/confirm-sent')
            ->assertRedirect('/accountability/' . $acc->id)
            ->assertSessionHas('success');

        $this->assertSame('confirmado', $this->statusOf($acc, 1));
        $this->assertSame('confirmado', $this->statusOf($acc, 2));
        $this->assertSame('rejeitado', $this->statusOf($acc, 3));
        $this->assertTrue(AccountabilityHistory::where('accountability_id', $acc->id)->where('status', 'like', '%em lote%')->exists());
    }

    public function test_confirmacao_em_lote_sem_enviados_avisa(): void
    {
        $acc = $this->makeAccountability([1]);
        $this->loginAs($this->makeUser('admin'));

        $this->post('/accountability/' . $acc->id . '/confirm-sent')->assertSessionHas('error');
    }

    // ---------- Duplicar ----------

    public function test_duplica_prestacao_pausada_com_os_mesmos_itens(): void
    {
        $source = $this->makeAccountability([2, 5, 9], ['title' => 'Prestacao Abril', 'description' => 'Desc', 'deadline' => '2027-04-30']);
        $this->loginAs($this->makeUser('admin'));

        $response = $this->post('/accountability/' . $source->id . '/duplicate');

        $copy = Accountability::where('id', '!=', $source->id)->firstOrFail();
        $response->assertRedirect('/accountability/' . $copy->id . '/edit');
        $this->assertSame('Cópia de Prestacao Abril', $copy->title);
        $this->assertSame('Desc', $copy->description);
        $this->assertNull($copy->deadline);
        $this->assertTrue((bool) $copy->is_paused);
        $this->assertSame([2, 5, 9], json_decode((string) $copy->enabled_items, true));
    }

    // ---------- Fila "Para avaliar" ----------

    public function test_fila_para_avaliar_lista_somente_itens_enviados_com_anexo(): void
    {
        $accA = $this->makeAccountability([1, 2], ['title' => 'Prestacao Alfa']);
        $accB = $this->makeAccountability([1], ['title' => 'Prestacao Beta']);
        $user = $this->makeUser('user', [1, 2], ['name' => 'Joana Envia']);
        $this->makeAttachment($accA, 1, $user);
        $this->makeAttachment($accB, 1, $user);
        $this->setItemStatus($accA, 1, 'enviado');
        $this->setItemStatus($accA, 2, 'enviado'); // sem anexo: fica fora
        $this->setItemStatus($accB, 1, 'confirmado');
        $this->loginAs($this->makeUser('admin'));

        $this->get('/review')
            ->assertOk()
            ->assertSee('Prestacao Alfa')
            ->assertSee('Joana Envia')
            ->assertSee('Para Avaliar (1)')
            ->assertDontSee('Prestacao Beta');
    }

    // ---------- Pendências por pessoa ----------

    public function test_pendencias_por_pessoa(): void
    {
        $acc    = $this->makeAccountability([1, 2, 3], ['title' => 'Prestacao Junho', 'deadline' => '2020-01-31']);
        $paused = $this->makeAccountability([1], ['title' => 'Prestacao Pausada', 'is_paused' => true]);
        $maria  = $this->makeUser('user', [1, 2, 3], ['name' => 'Maria Atrasada', 'whatsapp' => '(11) 98765-4321']);
        $this->makeUser('user', [], ['name' => 'Sem Itens']);
        $this->makeAttachment($acc, 2, $maria);
        $this->setItemStatus($acc, 2, 'enviado');
        $this->setItemStatus($acc, 3, 'rejeitado');
        $this->loginAs($this->makeUser('admin'));

        $res = $this->get('/pendencias')->assertOk();
        $res->assertSee('Maria Atrasada')
            ->assertSee('Prestacao Junho')
            ->assertSee('https://wa.me/5511987654321', false)
            ->assertDontSee('Prestacao Pausada')
            ->assertDontSee('Sem Itens');

        $rows = app(\App\Services\PendingByUserService::class)->build();
        $this->assertCount(1, $rows);
        $this->assertSame([1, 3], array_column($rows[0]['items'], 'item_number'));
        $this->assertSame(['prazo_vencido', 'rejeitado'], array_column($rows[0]['items'], 'status'));
        $this->assertSame(2, $rows[0]['overdue_count']);
    }

    public function test_usuario_comum_nao_acessa_ferramentas_do_admin(): void
    {
        $acc = $this->makeAccountability([1]);
        $this->loginAs($this->makeUser('user', [1]));

        $this->get('/review')->assertRedirect('/accountability');
        $this->get('/pendencias')->assertRedirect('/accountability');
        $this->post('/accountability/' . $acc->id . '/confirm-sent')->assertRedirect('/accountability');
        $this->post('/accountability/' . $acc->id . '/duplicate')->assertRedirect('/accountability');
        $this->assertSame(1, Accountability::count());
    }

    // ---------- API mobile ----------

    public function test_admin_avalia_item_pelo_app(): void
    {
        $acc     = $this->makeAccountability([1]);
        $user    = $this->makeUser('user', [1]);
        $headers = $this->mobileHeaders($this->makeUser('admin'));
        $this->makeAttachment($acc, 1, $user);
        $url = '/api/mobile/accountabilities/' . $acc->id . '/items/1/status';

        $this->getJson('/api/mobile/accountabilities/' . $acc->id, $headers)
            ->assertJsonPath('data.rejection_presets', ItemReviewService::REJECTION_PRESETS);

        $this->postJson($url, ['status' => 'rejeitado'], $headers)->assertStatus(422);
        $this->postJson($url, ['status' => 'rejeitado', 'rejection_reason' => 'Ilegivel'], $headers)
            ->assertOk()
            ->assertJsonPath('status', 'rejeitado')
            ->assertJsonPath('notified', 1);
        $this->postJson($url, ['status' => 'confirmado'], $headers)->assertOk()->assertJsonPath('status', 'confirmado');

        $this->assertSame('confirmado', $this->statusOf($acc, 1));
    }

    public function test_usuario_comum_nao_avalia_pelo_app(): void
    {
        $acc     = $this->makeAccountability([1]);
        $user    = $this->makeUser('user', [1]);
        $headers = $this->mobileHeaders($user);
        $this->makeAttachment($acc, 1, $user);

        $this->postJson('/api/mobile/accountabilities/' . $acc->id . '/items/1/status', ['status' => 'confirmado'], $headers)->assertForbidden();
        $this->postJson('/api/mobile/accountabilities/' . $acc->id . '/confirm-sent', [], $headers)->assertForbidden();
        $this->getJson('/api/mobile/review-queue', $headers)->assertForbidden();
        $this->getJson('/api/mobile/pending-by-user', $headers)->assertForbidden();
        $this->getJson('/api/mobile/accountabilities/' . $acc->id, $headers)->assertJsonPath('data.rejection_presets', []);
    }

    public function test_app_confirma_em_lote_e_lista_fila_e_pendencias(): void
    {
        $acc     = $this->makeAccountability([1, 2], ['title' => 'Prestacao App']);
        $user    = $this->makeUser('user', [1, 2], ['name' => 'Pedro App']);
        $headers = $this->mobileHeaders($this->makeUser('admin'));
        $this->makeAttachment($acc, 1, $user);
        $this->setItemStatus($acc, 1, 'enviado');

        $this->getJson('/api/mobile/review-queue', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.item_number', 1)
            ->assertJsonPath('data.0.uploaders', ['Pedro App']);

        $this->getJson('/api/mobile/pending-by-user', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.user.name', 'Pedro App')
            ->assertJsonPath('data.0.items.0.item_number', 2);
        $this->assertStringContainsString('Prestacao App', (string) $this->getJson('/api/mobile/pending-by-user', $headers)->json('data.0.reminder_text'));

        $this->postJson('/api/mobile/accountabilities/' . $acc->id . '/confirm-sent', [], $headers)
            ->assertOk()
            ->assertJsonPath('confirmed', 1);
        $this->getJson('/api/mobile/review-queue', $headers)->assertJsonCount(0, 'data');
    }
}
