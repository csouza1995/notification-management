# 🚀 Instruções para Publicar no GitHub

## 1. Criar Repositório no GitHub

1. Acesse: https://github.com/new
2. Nome do repositório: `notification-management`
3. Descrição: `Laravel package for managing user notification preferences across multiple channels`
4. Visibilidade: **Public**
5. **NÃO** marque "Initialize this repository with a README"
6. Clique em "Create repository"

## 2. Adicionar Remote e Push

No terminal, execute:

```bash
cd /home/csouza/projects/csouza/notification-management

# Verificar se já tem remote
git remote -v

# Se não tiver, adicionar
git remote add origin git@github.com:csouza1995/notification-management.git

# Se já tiver com nome diferente, atualizar
git remote set-url origin git@github.com:csouza1995/notification-management.git

# Fazer commit inicial
git commit -m "Initial release - Laravel Notification Management

Features:
- User notification preferences system
- Multi-channel support (mail, database, broadcast + custom)
- Channel registry for custom channels
- Automatic event-to-notification mapping
- REST API with 7 endpoints
- Notification logging
- Force/allowed channels
- Built-in UserLoggedNotification
- 37 tests, 75 assertions
- PHPStan level 5
- Complete documentation
"

# Push para GitHub
git push -u origin main
```

## 3. Configurar GitHub Actions

As actions já estão configuradas em `.github/workflows/`:
- `run-tests.yml` - Roda testes em PHP 8.1, 8.2, 8.3 com Laravel 10 e 11
- `phpstan.yml` - Análise estática de código
- `fix-php-code-style-issues.yml` - Formatação automática

Elas vão rodar automaticamente após o primeiro push!

## 4. Criar Release (Opcional)

1. No GitHub, vá em: **Releases** → **Create a new release**
2. Tag: `v1.0.0`
3. Title: `v1.0.0 - Initial Release`
4. Description: Cole o conteúdo do CHANGELOG.md
5. Clique em "Publish release"

## 5. Publicar no Packagist (Quando estiver pronto)

1. Acesse: https://packagist.org/packages/submit
2. Cole a URL: `https://github.com/csouza1995/notification-management`
3. Clique em "Check"
4. Configure auto-update webhook no GitHub

## 6. Adicionar Badges (Opcional)

No README.md, as badges já estão configuradas:
- ✅ Latest Version (Packagist)
- ✅ Total Downloads (Packagist)
- ✅ Tests Status (GitHub Actions)
- ✅ Code Style (GitHub Actions)

Elas funcionarão automaticamente após publicar no Packagist!

## 7. Checklist Final

Antes de publicar:
- [ ] Todos os testes passando: `composer test`
- [ ] PHPStan sem erros: `composer analyse`
- [ ] Código formatado: `composer format`
- [ ] README.md revisado
- [ ] CHANGELOG.md atualizado
- [ ] LICENSE.md correto
- [ ] composer.json com informações corretas
- [ ] .gitignore configurado

## 8. Comandos Úteis Pós-Publicação

```bash
# Ver status do repositório
git status

# Ver histórico de commits
git log --oneline

# Criar nova branch para features
git checkout -b feature/nova-funcionalidade

# Atualizar main
git checkout main
git pull origin main

# Tag para versão
git tag v1.0.0
git push origin v1.0.0
```

## 9. Próximos Passos

Após publicar:
1. ⭐ Teste o package em um projeto Laravel real
2. 📝 Adicione mais exemplos na documentação
3. 🎯 Implemente features faltantes (lote, rate limiting)
4. 📢 Compartilhe no Twitter/LinkedIn
5. 💬 Responda issues e PRs da comunidade

## Dúvidas?

- GitHub: https://github.com/csouza1995
- Email: carlossouza.work@gmail.com

---

**Boa sorte! 🚀**
