<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de Resposta</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 30px;
        }
        .alert-box {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-box.warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }
        .alert-box.info {
            background: #dbeafe;
            border-left-color: #3b82f6;
        }
        .info-grid {
            display: grid;
            gap: 15px;
            margin: 20px 0;
        }
        .info-item {
            padding: 12px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .info-item label {
            display: block;
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .info-item .value {
            font-size: 16px;
            color: #111827;
            font-weight: 500;
        }
        .score {
            display: inline-block;
            background: #dc2626;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 20px;
        }
        .comment-box {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            font-style: italic;
            color: #4b5563;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 Alerta de Resposta</h1>
    </div>

    <div class="content">
        <div class="alert-box {{ $category === 'Detrator' ? '' : ($category === 'Passivo' ? 'warning' : 'info') }}">
            <strong>Uma resposta {{ $category }} foi recebida!</strong>
            <br>
            Esta resposta atingiu o limite configurado para alertas (Score ≤ {{ $threshold }}).
        </div>

        <div class="info-grid">
            <div class="info-item">
                <label>Campanha</label>
                <div class="value">{{ $campaign_name }} ({{ $campaign_type }})</div>
            </div>

            <div class="info-item">
                <label>Respondente</label>
                <div class="value">
                    {{ $recipient_name }}
                    <br>
                    <span style="font-size: 14px; color: #6b7280;">{{ $recipient_email }}</span>
                </div>
            </div>

            <div class="info-item">
                <label>Pontuação</label>
                <div class="value">
                    <span class="score">{{ $score }}</span>
                    <span style="margin-left: 10px; color: #6b7280;">({{ $category }})</span>
                </div>
            </div>

            <div class="info-item">
                <label>Data da Resposta</label>
                <div class="value">{{ $submitted_at }}</div>
            </div>
        </div>

        @if($comment)
            <div class="info-item">
                <label>Comentário</label>
                <div class="comment-box">
                    "{{ $comment }}"
                </div>
            </div>
        @endif

        <p style="margin-top: 30px; color: #6b7280;">
            <strong>Próximos passos:</strong>
            <br>
            É recomendado entrar em contato com o cliente o mais rápido possível para entender melhor a experiência e trabalhar para melhorar a satisfação.
        </p>
    </div>

    <div class="footer">
        <p>
            Este é um alerta automático do sistema NPSFlow.
            <br>
            Você está recebendo este e-mail porque está configurado como destinatário de alertas.
        </p>
    </div>
</body>
</html>
