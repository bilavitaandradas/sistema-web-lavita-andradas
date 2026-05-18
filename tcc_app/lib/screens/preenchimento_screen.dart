import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import '../services/auth_service.dart';
import '../services/database_service.dart';

class PreenchimentoScreen extends StatefulWidget {
  final int idQuestionario;
  final String nomeQuestionario;

  const PreenchimentoScreen({
    super.key,
    required this.idQuestionario,
    required this.nomeQuestionario,
  });

  @override
  State<PreenchimentoScreen> createState() => _PreenchimentoScreenState();
}

class _PreenchimentoScreenState extends State<PreenchimentoScreen> {
  bool _isLoading = true;
  String _errorMessage = '';

  List<dynamic> _campos = [];

  final Map<int, TextEditingController> _controllers = {};
  final Map<int, String?> _dropdownValues = {};

  // NOVO: múltipla escolha
  final Map<int, List<String>> _checkboxValues = {};

  final Map<int, DateTime?> _pickedDates = {};
  final Map<int, TimeOfDay?> _pickedTimes = {};

  @override
  void initState() {
    super.initState();
    _fetchCamposFromLocalDB();
  }

  Future<void> _fetchCamposFromLocalDB() async {
    try {
      final campos = await DatabaseService.instance
          .getCamposDoQuestionarioLocal(widget.idQuestionario);

      if (mounted) {
        setState(() {
          _campos = campos;

          for (var campo in _campos) {
            final idCampo = campo['id_campo'] as int;
            final tipoCampo = campo['tipo_campo'];

            if (tipoCampo == 'DROPDOWN') {
              _dropdownValues[idCampo] = null;
            } else if (tipoCampo == 'CHECKBOX') {
              _checkboxValues[idCampo] = [];
            } else {
              _controllers[idCampo] = TextEditingController();
            }
          }

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
  void dispose() {
    for (var controller in _controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  // =========================================================
  // CONTROLE DE VISIBILIDADE (CAMPO DEPENDENTE)
  // =========================================================

  bool _campoDeveSerExibido(Map<String, dynamic> campo) {
    final dependenteDe = campo['dependente_de'];
    final dependenteValor = campo['dependente_valor'];

    // Campo normal
    if (dependenteDe == null ||
        dependenteValor == null ||
        dependenteValor.toString().isEmpty) {
      return true;
    }

    final int idCampoPai = dependenteDe as int;

    String? valorAtual;

    // Verifica dropdown
    if (_dropdownValues.containsKey(idCampoPai)) {
      valorAtual = _dropdownValues[idCampoPai];
    }

    // Verifica texto
    if (_controllers.containsKey(idCampoPai)) {
      valorAtual = _controllers[idCampoPai]?.text;
    }

    return valorAtual == dependenteValor;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.nomeQuestionario)),
      body: _buildBody(),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _isLoading ? null : _salvarOffline,
        label: const Text('Salvar Offline'),
        icon: const Icon(Icons.save),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_errorMessage.isNotEmpty) {
      return Center(child: Text('Erro: $_errorMessage'));
    }

    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
      itemCount: _campos.length,
      itemBuilder: (context, index) {
        final campo = _campos[index];

        // NOVO: esconde campo dependente
        if (!_campoDeveSerExibido(campo)) {
          return const SizedBox.shrink();
        }

        return _buildFormField(campo);
      },
    );
  }

  Widget _buildFormField(Map<String, dynamic> campo) {
    final idCampo = campo['id_campo'] as int;
    final tipoCampo = campo['tipo_campo'];
    final nomeCampo = campo['nome_campo'];

    switch (tipoCampo) {
      case 'TEXT':
        return _buildTextField(idCampo, nomeCampo);

      case 'NUMBER':
        return _buildTextField(idCampo, nomeCampo, isNumber: true);

      case 'DATE':
        return _buildDateField(idCampo, nomeCampo);

      case 'TIME':
        return _buildTimeField(idCampo, nomeCampo);

      case 'DROPDOWN':
        final List<dynamic> opcoes = jsonDecode(campo['opcoes'] ?? '[]');

        return _buildDropdownField(idCampo, nomeCampo, opcoes.cast<String>());

      // NOVO
      case 'CHECKBOX':
        final List<dynamic> opcoes = jsonDecode(campo['opcoes'] ?? '[]');

        return _buildCheckboxField(idCampo, nomeCampo, opcoes.cast<String>());

      default:
        return _buildTextField(idCampo, nomeCampo);
    }
  }

  Widget _buildTextField(
    int idCampo,
    String nomeCampo, {
    bool isNumber = false,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20.0),
      child: TextFormField(
        controller: _controllers[idCampo],
        decoration: InputDecoration(
          labelText: nomeCampo,
          border: const OutlineInputBorder(),
        ),
        onChanged: (_) {
          // Atualiza campos dependentes
          setState(() {});
        },
        keyboardType:
            isNumber
                ? const TextInputType.numberWithOptions(decimal: true)
                : TextInputType.text,
        inputFormatters:
            isNumber
                ? [FilteringTextInputFormatter.allow(RegExp(r'^\d*[.,]?\d*$'))]
                : [],
      ),
    );
  }

  Widget _buildDateField(int idCampo, String nomeCampo) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20.0),
      child: TextFormField(
        controller: _controllers[idCampo],
        readOnly: true,
        decoration: InputDecoration(
          labelText: nomeCampo,
          border: const OutlineInputBorder(),
          suffixIcon: const Icon(Icons.calendar_today),
        ),
        onTap: () async {
          FocusScope.of(context).requestFocus(FocusNode());

          DateTime? pickedDate = await showDatePicker(
            context: context,
            initialDate: _pickedDates[idCampo] ?? DateTime.now(),
            firstDate: DateTime(2000),
            lastDate: DateTime(2101),
          );

          if (pickedDate != null) {
            setState(() {
              _pickedDates[idCampo] = pickedDate;

              _controllers[idCampo]!.text = DateFormat(
                'dd/MM/yyyy',
              ).format(pickedDate);
            });
          }
        },
      ),
    );
  }

  Widget _buildTimeField(int idCampo, String nomeCampo) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20.0),
      child: TextFormField(
        controller: _controllers[idCampo],
        readOnly: true,
        decoration: InputDecoration(
          labelText: nomeCampo,
          border: const OutlineInputBorder(),
          suffixIcon: const Icon(Icons.access_time),
        ),
        onTap: () async {
          FocusScope.of(context).requestFocus(FocusNode());

          TimeOfDay? pickedTime = await showTimePicker(
            context: context,
            initialTime: _pickedTimes[idCampo] ?? TimeOfDay.now(),
            builder: (context, child) {
              return MediaQuery(
                data: MediaQuery.of(
                  context,
                ).copyWith(alwaysUse24HourFormat: true),
                child: child!,
              );
            },
          );

          if (pickedTime != null) {
            setState(() {
              _pickedTimes[idCampo] = pickedTime;

              final hour = pickedTime.hour.toString().padLeft(2, '0');

              final minute = pickedTime.minute.toString().padLeft(2, '0');

              _controllers[idCampo]!.text = '$hour:$minute';
            });
          }
        },
      ),
    );
  }

  Widget _buildDropdownField(
    int idCampo,
    String nomeCampo,
    List<String> opcoes,
  ) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20.0),
      child: DropdownButtonFormField<String>(
        initialValue: _dropdownValues[idCampo],
        decoration: InputDecoration(
          labelText: nomeCampo,
          border: const OutlineInputBorder(),
        ),
        hint: const Text('Selecione...'),
        onChanged: (String? newValue) {
          setState(() {
            _dropdownValues[idCampo] = newValue;
          });
        },
        items:
            opcoes.map<DropdownMenuItem<String>>((String valor) {
              return DropdownMenuItem<String>(value: valor, child: Text(valor));
            }).toList(),
      ),
    );
  }

  // =========================================================
  // NOVO CAMPO CHECKBOX
  // =========================================================

  Widget _buildCheckboxField(
    int idCampo,
    String nomeCampo,
    List<String> opcoes,
  ) {
    final valoresSelecionados = _checkboxValues[idCampo] ?? [];

    return Padding(
      padding: const EdgeInsets.only(bottom: 20.0),
      child: InkWell(
        onTap: () async {
          final selecionadosTemp = List<String>.from(valoresSelecionados);

          await showModalBottomSheet(
            context: context,
            isScrollControlled: true,
            builder: (context) {
              return StatefulBuilder(
                builder: (context, setModalState) {
                  return Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          nomeCampo,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),

                        const SizedBox(height: 16),

                        Flexible(
                          child: ListView(
                            shrinkWrap: true,
                            children:
                                opcoes.map((opcao) {
                                  final selecionado = selecionadosTemp.contains(
                                    opcao,
                                  );

                                  return CheckboxListTile(
                                    title: Text(opcao),
                                    value: selecionado,
                                    onChanged: (bool? value) {
                                      setModalState(() {
                                        if (value == true) {
                                          selecionadosTemp.add(opcao);
                                        } else {
                                          selecionadosTemp.remove(opcao);
                                        }
                                      });
                                    },
                                  );
                                }).toList(),
                          ),
                        ),

                        const SizedBox(height: 12),

                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: () {
                              setState(() {
                                _checkboxValues[idCampo] = selecionadosTemp;
                              });

                              Navigator.pop(context);
                            },
                            child: const Text('Confirmar'),
                          ),
                        ),
                      ],
                    ),
                  );
                },
              );
            },
          );
        },

        child: InputDecorator(
          decoration: InputDecoration(
            labelText: nomeCampo,
            border: const OutlineInputBorder(),
            suffixIcon: const Icon(Icons.arrow_drop_down),
          ),

          child: Text(
            valoresSelecionados.isEmpty
                ? 'Selecione...'
                : valoresSelecionados.join(', '),
            style: TextStyle(
              color: valoresSelecionados.isEmpty ? Colors.grey : Colors.black,
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _salvarOffline() async {
    try {
      final respostasMap = <String, dynamic>{};

      for (var campo in _campos) {
        // Ignora campos escondidos
        if (!_campoDeveSerExibido(campo)) {
          continue;
        }

        final idCampo = campo['id_campo'] as int;

        final tipoCampo = campo['tipo_campo'];

        String? valorParaSalvar;

        switch (tipoCampo) {
          case 'DATE':
            if (_pickedDates[idCampo] != null) {
              valorParaSalvar = DateFormat(
                'yyyy-MM-dd',
              ).format(_pickedDates[idCampo]!);
            }
            break;

          case 'TIME':
            if (_pickedTimes[idCampo] != null) {
              final hour = _pickedTimes[idCampo]!.hour.toString().padLeft(
                2,
                '0',
              );

              final minute = _pickedTimes[idCampo]!.minute.toString().padLeft(
                2,
                '0',
              );

              valorParaSalvar = '$hour:$minute';
            }
            break;

          case 'DROPDOWN':
            valorParaSalvar = _dropdownValues[idCampo];
            break;

          // NOVO
          case 'CHECKBOX':
            final valores = _checkboxValues[idCampo] ?? [];

            valorParaSalvar = jsonEncode(valores);
            break;

          default:
            valorParaSalvar = _controllers[idCampo]?.text;
            break;
        }

        if (valorParaSalvar == null || valorParaSalvar.isEmpty) {
          throw Exception('O campo "${campo['nome_campo']}" é obrigatório.');
        }

        respostasMap[idCampo.toString()] = valorParaSalvar;
      }

      final userIdString = await AuthService.instance.getUserId();

      if (userIdString == null) {
        throw Exception('ID do usuário não encontrado.');
      }

      final userId = int.parse(userIdString);

      final lancamentoData = {
        'id_lancamento': '${userId}_${DateTime.now().millisecondsSinceEpoch}',

        'id_questionario': widget.idQuestionario,

        'id_usuario': userId,

        'respostas': jsonEncode(respostasMap),

        'criado_em_local': DateTime.now().toIso8601String(),

        'sincronizado': 0,
      };

      await DatabaseService.instance.createLancamento(lancamentoData);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Lançamento salvo com sucesso offline!'),
            backgroundColor: Colors.green,
          ),
        );

        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Erro ao salvar: ${e.toString().replaceAll("Exception: ", "")}',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }
}
