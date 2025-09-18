import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../services/database_service.dart';

class ConfiguracoesScreen extends StatefulWidget {
  const ConfiguracoesScreen({super.key});

  @override
  State<ConfiguracoesScreen> createState() => _ConfiguracoesScreenState();
}

class _ConfiguracoesScreenState extends State<ConfiguracoesScreen> {
  String _nome = 'Carregando...';
  String _username = 'Carregando...';

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  // Busca os dados do usuário que salvamos no armazenamento seguro
  Future<void> _loadUserData() async {
    final userData = await AuthService.instance.getUserData();
    if (mounted) {
      setState(() {
        _nome = userData['nome'] ?? 'Não encontrado';
        _username = userData['username'] ?? 'Não encontrado';
      });
    }
  }

  // --- FUNÇÃO CORRETA: Limpa apenas os dados locais ---
  Future<void> _limparDadosOffline() async {
    final confirmado = await showDialog<bool>(
      context: context,
      builder:
          (context) => AlertDialog(
            title: const Text('Limpar Dados Offline'),
            content: const Text(
              'Isso apagará TODOS os lançamentos pendentes que ainda não foram sincronizados do seu celular. Os dados no servidor não serão afetados. Deseja continuar?',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Cancelar'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text(
                  'Limpar',
                  style: TextStyle(color: Colors.red),
                ),
              ),
            ],
          ),
    );

    if (confirmado == true && mounted) {
      // Chama a função do nosso DatabaseService que apaga os registros do SQLite
      final count = await DatabaseService.instance.clearLancamentosPendentes();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('$count lançamentos offline foram apagados.'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Configurações')),
      body: ListView(
        padding: const EdgeInsets.all(16.0),
        children: [
          const Text(
            'Informações do Usuário',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.blue,
            ),
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.person_outline),
            title: const Text('Nome'),
            subtitle: Text(_nome),
          ),
          ListTile(
            leading: const Icon(Icons.email_outlined),
            title: const Text('Usuário de Login'),
            subtitle: Text(_username),
          ),
          const SizedBox(height: 30),
          const Text(
            'Ações do Aplicativo',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.blue,
            ),
          ),
          const Divider(),
          // O ListTile agora chama a função correta
          ListTile(
            leading: Icon(
              Icons.delete_sweep_outlined,
              color: Colors.orange.shade800,
            ),
            title: Text(
              'Limpar Lançamentos Offline',
              style: TextStyle(color: Colors.orange.shade800),
            ),
            subtitle: const Text(
              'Apaga todos os dados salvos no celular que ainda não foram sincronizados.',
            ),
            onTap: _limparDadosOffline,
          ),
          const SizedBox(height: 30),
          const Text(
            'Sobre o App',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.blue,
            ),
          ),
          const Divider(),
          const ListTile(
            leading: Icon(Icons.info_outline),
            title: Text('Versão'),
            subtitle: Text('1.0.0'),
          ),
        ],
      ),
    );
  }
}
