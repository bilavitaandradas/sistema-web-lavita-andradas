import 'package:flutter/material.dart';
import 'package:tcc_app/screens/auth_check_screen.dart';
import 'package:tcc_app/services/auth_service.dart';
import 'package:tcc_app/services/api_service.dart';
import 'package:tcc_app/services/database_service.dart';
import 'configuracoes_screen.dart';
import 'gerenciar_offline_screen.dart';
import 'meus_lancamentos_screen.dart';
import 'questionario_list_screen.dart';

class HomeScreen extends StatefulWidget {
  final String nome;
  const HomeScreen({super.key, required this.nome});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  List<dynamic> _questionariosOnline = [];
  int _lancamentosPendentes = 0;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _sincronizarTudo(
      showMessages: false,
    ); // Sincronização silenciosa na inicialização
  }

  Future<void> _loadData({bool showLoading = true}) async {
    if (!mounted) return;
    if (showLoading && !_isLoading) setState(() => _isLoading = true);

    try {
      final results = await Future.wait([
        DatabaseService.instance.getQuestionariosLocais(),
        DatabaseService.instance.countLancamentosPendentes(),
      ]);
      if (mounted) {
        setState(() {
          _questionariosOnline = results[0] as List<dynamic>;
          _lancamentosPendentes = results[1] as int;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erro ao carregar dados locais: $e')),
        );
      }
    }
  }

  Future<void> _sincronizarTudo({bool showMessages = true}) async {
    if (mounted) setState(() => _isLoading = true);

    if (showMessages) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Sincronizando dados...'),
          duration: Duration(seconds: 2),
        ),
      );
    }

    try {
      final token = await AuthService.instance.getToken();
      if (token == null) throw Exception("Token não encontrado");

      final lancamentosParaSincronizar =
          await DatabaseService.instance.getLancamentosPendentes();
      if (lancamentosParaSincronizar.isNotEmpty) {
        final responseUp = await ApiService.sincronizarLancamentos(
          token,
          lancamentosParaSincronizar,
        );
        if (responseUp['success'] == true) {
          final idsSincronizados =
              lancamentosParaSincronizar
                  .map((l) => l['id_lancamento'] as String)
                  .toList();
          await DatabaseService.instance.marcarComoSincronizado(
            idsSincronizados,
          );
        }
      }

      final dadosParaCache = await ApiService.getDadosParaSincronizar(token);
      await DatabaseService.instance.salvarCacheDeSincronizacao(dadosParaCache);

      if (mounted && showMessages) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Sincronização concluída!'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } catch (e) {
      if (mounted && showMessages) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Falha na sincronização. Verifique sua conexão.'),
            backgroundColor: Colors.orange,
          ),
        );
      }
    }

    await _loadData(showLoading: false);
  }

  Future<void> _logout() async {
    final confirmado = await showDialog<bool>(
      context: context,
      builder:
          (context) => AlertDialog(
            title: const Text('Confirmar Logout'),
            content: const Text('Tem certeza que deseja sair?'),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Cancelar'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Sair'),
              ),
            ],
          ),
    );
    if (confirmado == true && mounted) {
      await AuthService.instance.deleteSession();
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (context) => const AuthCheckScreen()),
        (Route<dynamic> route) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.green,
        automaticallyImplyLeading: false,
      ),
      backgroundColor: Colors.grey[100],
      body: Column(
        children: [
          _buildHeader(context, widget.nome),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                children: [
                  InkWell(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder:
                              (context) => GerenciarOfflineScreen(
                                questionariosOnline: _questionariosOnline,
                              ),
                        ),
                      ).then((_) => _loadData());
                    },
                    borderRadius: BorderRadius.circular(12),
                    child: _buildSummaryCard(
                      context: context,
                      title: 'Lançamentos Offline',
                      subtitle: '$_lancamentosPendentes pendentes',
                      icon: Icons.cloud_off,
                      color: Colors.orange,
                    ),
                  ),
                  const SizedBox(height: 24),
                  _buildMenuButton(
                    context,
                    title: 'Questionários',
                    icon: Icons.list_alt,
                    onTap: () async {
                      await Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const QuestionarioListScreen(),
                        ),
                      );
                      _loadData();
                    },
                  ),
                  _buildMenuButton(
                    context,
                    title: 'Meus Lançamentos',
                    icon: Icons.check_circle_outline,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const MeusLancamentosScreen(),
                        ),
                      );
                    },
                  ),

                  // --- NOVO BOTÃO DE SINCRONIZAR ---
                  _buildMenuButton(
                    context,
                    title: 'Sincronizar Dados',
                    icon: Icons.sync,
                    color: Colors.teal,
                    onTap:
                        _isLoading
                            ? null
                            : () => _sincronizarTudo(showMessages: true),
                  ),

                  _buildMenuButton(
                    context,
                    title: 'Configurações',
                    icon: Icons.settings,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const ConfiguracoesScreen(),
                        ),
                      ).then((_) => _loadData());
                    },
                  ),
                  const SizedBox(height: 16),
                  _buildMenuButton(
                    context,
                    title: 'Logout',
                    icon: Icons.logout,
                    color: Colors.red,
                    onTap: _logout,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader(BuildContext context, String userName) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      decoration: const BoxDecoration(
        color: Colors.green,
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(20)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Olá, $userName!',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 24,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          _isLoading
              ? const Text(
                'Carregando...',
                style: TextStyle(color: Colors.white70, fontSize: 16),
              )
              : Text(
                '${_questionariosOnline.length} questionários disponíveis',
                style: const TextStyle(color: Colors.white70, fontSize: 16),
              ),
        ],
      ),
    );
  }

  Widget _buildSummaryCard({
    required BuildContext context,
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
  }) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Row(
          children: [
            Icon(icon, color: color, size: 32),
            const SizedBox(width: 16),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
                _isLoading
                    ? Text(
                      'Carregando...',
                      style: TextStyle(color: Colors.grey[600]),
                    )
                    : Text(subtitle, style: TextStyle(color: Colors.grey[600])),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMenuButton(
    BuildContext context, {
    required String title,
    required IconData icon,
    required VoidCallback? onTap,
    Color? color,
  }) {
    return Card(
      elevation: 1,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 16.0, horizontal: 20.0),
          child: Row(
            children: [
              Icon(
                icon,
                color: onTap == null ? Colors.grey : color ?? Colors.green,
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                    color:
                        onTap == null ? Colors.grey : color ?? Colors.black87,
                  ),
                ),
              ),
              if (title != 'Logout')
                Icon(
                  Icons.arrow_forward_ios,
                  color: Colors.grey[400],
                  size: 16,
                ),
            ],
          ),
        ),
      ),
    );
  }
}
