import 'package:flutter/material.dart';
import 'package:tcc_app/screens/preenchimento_screen.dart';
import '../services/database_service.dart';

class QuestionarioListScreen extends StatefulWidget {
  const QuestionarioListScreen({super.key});

  @override
  State<QuestionarioListScreen> createState() => _QuestionarioListScreenState();
}

class _QuestionarioListScreenState extends State<QuestionarioListScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _questionarios = [];
  String _errorMessage = '';

  @override
  void initState() {
    super.initState();
    _loadQuestionariosFromLocalDB();
  }

  Future<void> _loadQuestionariosFromLocalDB() async {
    try {
      final data = await DatabaseService.instance.getQuestionariosLocais();
      if (mounted) {
        setState(() {
          _questionarios = data;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = e.toString();
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Questionários"),
        centerTitle: true,
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_errorMessage.isNotEmpty) {
      return Center(child: Text(_errorMessage));
    }
    if (_questionarios.isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(16.0),
          child: Text(
            "Nenhum questionário encontrado. Tente sincronizar na tela principal.",
            textAlign: TextAlign.center,
          ),
        ),
      );
    }
    return ListView.builder(
      padding: const EdgeInsets.all(8.0),
      itemCount: _questionarios.length,
      itemBuilder: (context, index) {
        final q = _questionarios[index];
        return Card(
          margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 8),
          child: ListTile(
            title: Text(q['nome_questionario']),
            subtitle: Text(q['descricao_questionario'] ?? ''),
            trailing: const Icon(Icons.arrow_forward_ios, size: 16),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => PreenchimentoScreen(
                    idQuestionario: q['id_questionario'],
                    nomeQuestionario: q['nome_questionario'],
                  ),
                ),
              );
            },
          ),
        );
      },
    );
  }
}