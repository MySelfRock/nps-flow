<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Campaign;
use App\Models\Recipient;
use App\Models\Response;
use App\Models\Send;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating demo tenant...');

        // Create demo tenant
        $tenant = Tenant::create([
            'name' => 'Clínica Saúde & Bem-Estar',
            'cnpj' => '12.345.678/0001-90',
            'plan' => 'pro',
        ]);

        $this->command->info("Tenant created: {$tenant->name}");

        // Create users
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Demo',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Gerente Demo',
            'email' => 'gerente@demo.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
        ]);

        $this->command->info('Users created: admin@demo.com / gerente@demo.com (password: password123)');

        // Create NPS Campaign
        $npsCampaign = Campaign::create([
            'tenant_id' => $tenant->id,
            'name' => 'Pesquisa de Satisfação - Atendimento 2024',
            'type' => 'NPS',
            'message_template' => [
                'subject' => 'Como foi sua experiência na Clínica Saúde & Bem-Estar?',
                'body' => 'Olá {{name}},

Em uma escala de 0 a 10, quanto você recomendaria nossa clínica a um amigo ou familiar?

Clique no link para responder: {{link}}

Agradecemos seu feedback!
Equipe Clínica Saúde & Bem-Estar'
            ],
            'sender_email' => 'contato@clinicasaude.com',
            'sender_name' => 'Clínica Saúde & Bem-Estar',
            'status' => 'sent',
            'created_by' => $admin->id,
        ]);

        // Create CSAT Campaign
        $csatCampaign = Campaign::create([
            'tenant_id' => $tenant->id,
            'name' => 'Avaliação Pós-Consulta',
            'type' => 'CSAT',
            'message_template' => [
                'subject' => 'Avalie sua consulta',
                'body' => 'Olá {{name}},

Qual seu nível de satisfação com a consulta realizada?

Responda em: {{link}}

Obrigado!'
            ],
            'sender_email' => 'contato@clinicasaude.com',
            'sender_name' => 'Clínica Saúde & Bem-Estar',
            'status' => 'sending',
            'created_by' => $manager->id,
        ]);

        $this->command->info('Campaigns created');

        // Create alert for NPS campaign
        Alert::create([
            'tenant_id' => $tenant->id,
            'campaign_id' => $npsCampaign->id,
            'condition' => [
                'score_threshold' => 6
            ],
            'notify_emails' => ['admin@demo.com', 'gerente@demo.com'],
            'webhook_url' => null,
            'enabled' => true,
        ]);

        $this->command->info('Alert created for low NPS scores');

        // Create recipients for NPS campaign
        $recipients = [
            ['name' => 'João Silva', 'email' => 'joao.silva@example.com', 'phone' => '+5511999990001'],
            ['name' => 'Maria Santos', 'email' => 'maria.santos@example.com', 'phone' => '+5511999990002'],
            ['name' => 'Pedro Oliveira', 'email' => 'pedro.oliveira@example.com', 'phone' => '+5511999990003'],
            ['name' => 'Ana Costa', 'email' => 'ana.costa@example.com', 'phone' => '+5511999990004'],
            ['name' => 'Carlos Ferreira', 'email' => 'carlos.ferreira@example.com', 'phone' => '+5511999990005'],
            ['name' => 'Juliana Rodrigues', 'email' => 'juliana.rodrigues@example.com', 'phone' => '+5511999990006'],
            ['name' => 'Roberto Almeida', 'email' => 'roberto.almeida@example.com', 'phone' => '+5511999990007'],
            ['name' => 'Fernanda Lima', 'email' => 'fernanda.lima@example.com', 'phone' => '+5511999990008'],
            ['name' => 'Paulo Martins', 'email' => 'paulo.martins@example.com', 'phone' => '+5511999990009'],
            ['name' => 'Beatriz Souza', 'email' => 'beatriz.souza@example.com', 'phone' => '+5511999990010'],
        ];

        $createdRecipients = [];

        foreach ($recipients as $recipientData) {
            $recipient = Recipient::create([
                'tenant_id' => $tenant->id,
                'campaign_id' => $npsCampaign->id,
                'name' => $recipientData['name'],
                'email' => $recipientData['email'],
                'phone' => $recipientData['phone'],
                'external_id' => 'CUST' . rand(1000, 9999),
                'status' => 'sent',
                'tags' => rand(0, 1) ? ['vip'] : [],
            ]);

            $createdRecipients[] = $recipient;

            // Create send record
            Send::create([
                'tenant_id' => $tenant->id,
                'campaign_id' => $npsCampaign->id,
                'recipient_id' => $recipient->id,
                'channel' => 'email',
                'status' => 'delivered',
                'provider_message_id' => 'msg_' . uniqid(),
                'attempts' => 1,
                'last_attempt_at' => now()->subDays(rand(1, 7)),
            ]);
        }

        $this->command->info('Recipients created with send records');

        // Create responses (simulate different NPS scores)
        $scores = [10, 9, 9, 8, 7, 6, 5, 4, 9, 10]; // Mix of promoters, passives, and detractors

        foreach ($createdRecipients as $index => $recipient) {
            if ($index < 8) { // Only 8 out of 10 responded
                $score = $scores[$index];

                $comments = [
                    10 => 'Excelente atendimento! Recomendo muito!',
                    9 => 'Muito bom, atendimento de qualidade.',
                    8 => 'Bom atendimento, mas pode melhorar.',
                    7 => 'Atendimento ok, nada excepcional.',
                    6 => 'Esperava mais, ficou na média.',
                    5 => 'Deixou a desejar em alguns pontos.',
                    4 => 'Não gostei muito do atendimento.',
                ];

                Response::create([
                    'tenant_id' => $tenant->id,
                    'campaign_id' => $npsCampaign->id,
                    'recipient_id' => $recipient->id,
                    'score' => $score,
                    'comment' => $comments[$score] ?? 'Sem comentários.',
                    'metadata' => [
                        'ip' => '192.168.1.' . rand(1, 255),
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'submitted_at' => now()->subDays(rand(1, 7))->toIso8601String(),
                    ],
                ]);

                $recipient->update(['status' => 'responded']);
            }
        }

        $this->command->info('Responses created (8 responses out of 10 recipients)');

        // Create recipients for CSAT campaign (fewer)
        $csatRecipients = [
            ['name' => 'Lucas Mendes', 'email' => 'lucas.mendes@example.com'],
            ['name' => 'Camila Barbosa', 'email' => 'camila.barbosa@example.com'],
            ['name' => 'Rafael Dias', 'email' => 'rafael.dias@example.com'],
        ];

        foreach ($csatRecipients as $recipientData) {
            Recipient::create([
                'tenant_id' => $tenant->id,
                'campaign_id' => $csatCampaign->id,
                'name' => $recipientData['name'],
                'email' => $recipientData['email'],
                'status' => 'pending',
            ]);
        }

        $this->command->info('CSAT campaign recipients created');

        // Summary
        $this->command->info('');
        $this->command->info('=== Demo Data Summary ===');
        $this->command->info("Tenant: {$tenant->name}");
        $this->command->info('Users:');
        $this->command->info('  - admin@demo.com (super_admin) - password: password123');
        $this->command->info('  - gerente@demo.com (manager) - password: password123');
        $this->command->info('');
        $this->command->info('Campaigns:');
        $this->command->info("  - NPS Campaign: {$npsCampaign->name} ({$npsCampaign->recipients()->count()} recipients, {$npsCampaign->responses()->count()} responses)");
        $this->command->info("  - CSAT Campaign: {$csatCampaign->name} ({$csatCampaign->recipients()->count()} recipients, {$csatCampaign->responses()->count()} responses)");
        $this->command->info('');

        // Calculate NPS
        $npsScore = $npsCampaign->getNPSScore();
        $responseRate = $npsCampaign->getResponseRate();

        $this->command->info("NPS Campaign Metrics:");
        $this->command->info("  - NPS Score: " . ($npsScore !== null ? round($npsScore, 2) : 'N/A'));
        $this->command->info("  - Response Rate: " . round($responseRate, 2) . "%");
        $this->command->info('');
        $this->command->info('✅ Demo data seeded successfully!');
    }
}
