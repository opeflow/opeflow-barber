# Guia do administrador da plataforma

## Ambiente local de desenvolvimento

O painel global fica em `/admin/entrar`. No ambiente local, use as credenciais demo descritas em [Acessos de desenvolvimento](./acessos-desenvolvimento.md). O atalho de desenvolvimento ignora a exigência de MFA para facilitar testes; ele não deve ser usado como configuração de produção.

## Acesso de produção

O painel responde como não encontrado em produção por padrão. A equipe responsável só deve habilitá-lo após revisar contas e políticas de acesso, guardar `APP_KEY` em cofre de segredos e configurar HTTPS, sessão segura, e-mail de operação e backup do banco.

1. Configure `ADMINISTRACAO_GLOBAL_HABILITADA=true` no ambiente de implantação.
2. Provisione uma conta individual pelo comando interativo `php artisan administracao:criar-administrador --nome="Nome" --email="admin@empresa.com"`. A senha é digitada sem eco e nunca deve ser passada como opção ou argumento. Não use nem crie a conta demo em produção.
3. Entre com a conta. No primeiro acesso, cadastre a chave mostrada no aplicativo autenticador e confirme um código TOTP de seis dígitos.
4. Guarde os códigos de recuperação exibidos uma única vez em um gerenciador seguro. Cada código só pode ser usado uma vez.
5. Encerre a sessão e teste novo acesso com o autenticador antes de considerar a ativação concluída.

As credenciais MFA são cifradas com `APP_KEY`; códigos de recuperação são armazenados como hash. Perder o autenticador e todos os códigos exige recuperação operacional da conta, não há atalho de acesso no painel.

## Auditoria e eventos

Em **Auditoria**, filtre os eventos administrativos por nível, serviço, período e identificador de requisição. A listagem tem paginação e descrições/metadata redigidas. Ela contém ações de autenticação, MFA e alterações de integração; não é um visualizador de arquivos brutos de log e não oferece console, SQL nem acesso arbitrário ao servidor.

## Integrações e credenciais

Em **Integrações**, cadastre um rótulo, provedor e chave, rotacione substituindo a chave ou revogue uma integração. O sistema cifra o valor no banco e mostra somente uma máscara; não há mecanismo para recuperar o segredo salvo. Ao rotacionar, atualize também o serviço provedor por procedimento próprio.

Esta tela administra armazenamento seguro, mas ainda não executa conectores nem testa conectividade. A API oficial do WhatsApp continua fora do escopo do MVP; cadastrar uma chave não envia mensagens nem ativa integração.

## Limites e próximos passos

A visão inicial apresenta indicadores agregados, estado de banco/Redis e quantidade de jobs falhos. Configurações globais, alertas, retenção/expurgo de auditoria, monitoramento de workers/agendador/armazenamento e integração com cofre gerenciado ainda precisam de implantação e validação operacional.
