import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';

class MeusLancamentosScreen extends StatefulWidget {
  const MeusLancamentosScreen({super.key});

  @override
  State<MeusLancamentosScreen> createState() => _MeusLancamentosScreenState();
}

class _MeusLancamentosScreenState extends State<MeusLancamentosScreen> {
  bool _isLoading = true;
  List<dynamic> _lancamentos = [];
  String _errorMessage = '';

  // Removemos todas as variáveis de estado da paginação

  @override
  void initState() {
    super.initState();
    _fetchLancamentos(); // Chamada simples
  }

  // Função de busca volta a ser simples
  Future<void> _fetchLancamentos() async {
    // Não precisamos mais do setState aqui no início
    try {
      final token = await AuthService.instance.getToken();
      if (token == null) throw Exception("Token não encontrado.");

      // A API agora retorna uma List diretamente
      final data = await ApiService.getMeusLancamentos(token);

      if (mounted) {
        setState(() {
          _lancamentos = data;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = e.toString().replaceAll("Exception: ", "");
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Últimos Lançamentos'), // Título mais apropriado
        centerTitle: true,
      ),
      // Removemos a barra de navegação 'bottomNavigationBar'
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_errorMessage.isNotEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Text(_errorMessage, textAlign: TextAlign.center),
        ),
      );
    }
    if (_lancamentos.isEmpty) {
      return const Center(
        child: Text("Você ainda não possui lançamentos sincronizados."),
      );
    }
    // A ListView volta a ser simples
    return ListView.builder(
      padding: const EdgeInsets.all(8.0),
      itemCount: _lancamentos.length,
      itemBuilder: (context, index) {
        final lancamento = _lancamentos[index];
        final dataFormatada = DateFormat(
          'dd/MM/yyyy HH:mm',
        ).format(DateTime.parse(lancamento['criado_em']));

        return Card(
          margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 8),
          child: ListTile(
            leading: const Icon(
              Icons.history_edu_outlined,
              color: Colors.blueGrey,
            ),
            title: Text(
              lancamento['nome_questionario'],
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            subtitle: Text('Realizado em: $dataFormatada'),
            onTap: () {},
          ),
        );
      },
    );
  }
}
