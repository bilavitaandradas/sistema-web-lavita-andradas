import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/database_service.dart'; // Apenas o DatabaseService é necessário aqui

class GerenciarOfflineScreen extends StatefulWidget {
  // A lista de questionários online ainda é útil para buscarmos os nomes rapidamente
  final List<dynamic> questionariosOnline;

  const GerenciarOfflineScreen({super.key, required this.questionariosOnline});

  @override
  State<GerenciarOfflineScreen> createState() => _GerenciarOfflineScreenState();
}

class _GerenciarOfflineScreenState extends State<GerenciarOfflineScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _lancamentosPendentes = [];

  @override
  void initState() {
    super.initState();
    _refreshLancamentos();
  }

  Future<void> _refreshLancamentos() async {
    if (!mounted) return;
    setState(() => _isLoading = true);
    final data = await DatabaseService.instance.getLancamentosPendentes();
    if (mounted) {
      setState(() {
        _lancamentosPendentes = data;
        _isLoading = false;
      });
    }
  }

  String _getQuestionarioNome(int id) {
    try {
      return widget.questionariosOnline.firstWhere(
        (q) => q['id_questionario'] == id,
      )['nome_questionario'];
    } catch (e) {
      return 'Questionário Desconhecido';
    }
  }

  Future<void> _deleteLancamento(String idLancamento) async {
    final confirmado = await showDialog<bool>(
      context: context,
      builder:
          (context) => AlertDialog(
            title: const Text('Confirmar Exclusão'),
            content: const Text(
              'Tem certeza que deseja apagar este lançamento permanentemente?',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Cancelar'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Excluir'),
              ),
            ],
          ),
    );

    if (confirmado == true) {
      await DatabaseService.instance.deleteLancamento(idLancamento);
      _refreshLancamentos();
    }
  }

  // --- FUNÇÃO DE DETALHES AGORA 100% OFFLINE ---
  Future<void> _showDetails(Map<String, dynamic> lancamento) async {
    final Map<String, dynamic> respostas = jsonDecode(lancamento['respostas']);

    // A função de busca agora chama o DatabaseService, não mais o ApiService.
    Future<List<dynamic>> fetchCamposLocais() async {
      return DatabaseService.instance.getCamposDoQuestionarioLocal(
        lancamento['id_questionario'],
      );
    }

    showDialog(
      context: context,
      builder:
          (context) => AlertDialog(
            title: const Text('Detalhes do Lançamento'),
            content: SizedBox(
              width: double.maxFinite,
              child: FutureBuilder<List<dynamic>>(
                future: fetchCamposLocais(), // Usa a nova função de busca local
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snapshot.hasError) {
                    return Text(
                      'Erro ao carregar nomes dos campos: ${snapshot.error}',
                    );
                  }
                  if (snapshot.hasData) {
                    final nomesCampos = {
                      for (var campo in snapshot.data!)
                        campo['id_campo']: campo['nome_campo'],
                    };
                    final tiposCampos = {
                      for (var c in snapshot.data!)
                        c['id_campo']: c['tipo_campo'],
                    };

                    return ListView(
                      shrinkWrap: true,
                      children:
                          respostas.entries.map((entry) {
                            final idCampo = int.tryParse(entry.key) ?? 0;
                            final nomeDoCampo =
                                nomesCampos[idCampo] ?? 'Campo Desconhecido';
                            String valorFormatado = entry.value.toString();
                            final tipoDoCampo = tiposCampos[idCampo] ?? 'TEXT';

                            if (tipoDoCampo == 'DATE') {
                              try {
                                final data = DateTime.parse(entry.value);
                                valorFormatado = DateFormat(
                                  'dd/MM/yyyy',
                                ).format(data);
                              } catch (e) {
                                valorFormatado = entry.value;
                              }
                            }
                            return ListTile(
                              title: Text(
                                nomeDoCampo,
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              subtitle: Text('Resposta: $valorFormatado'),
                            );
                          }).toList(),
                    );
                  }
                  return const Text('Nenhum campo encontrado.');
                },
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Fechar'),
              ),
            ],
          ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lançamentos Pendentes')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_lancamentosPendentes.isEmpty) {
      return const Center(child: Text('Nenhum lançamento pendente.'));
    }

    return ListView.builder(
      itemCount: _lancamentosPendentes.length,
      itemBuilder: (context, index) {
        final lancamento = _lancamentosPendentes[index];
        final nomeQuestionario = _getQuestionarioNome(
          lancamento['id_questionario'],
        );
        final dataLancamento = DateFormat(
          'dd/MM/yyyy HH:mm',
        ).format(DateTime.parse(lancamento['criado_em_local']));

        return Card(
          margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 12),
          child: ListTile(
            title: Text(nomeQuestionario),
            subtitle: Text('Salvo em: $dataLancamento'),
            onTap: () => _showDetails(lancamento),
            trailing: IconButton(
              icon: const Icon(Icons.delete_outline, color: Colors.red),
              onPressed: () => _deleteLancamento(lancamento['id_lancamento']),
            ),
          ),
        );
      },
    );
  }
}
