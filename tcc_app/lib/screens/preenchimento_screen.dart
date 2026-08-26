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

  List<Map<String, dynamic>> _dependencias = [];

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

      final dependencias = await DatabaseService.instance
          .getDependenciasDoQuestionarioLocal(widget.idQuestionario);

      if (mounted) {
        setState(() {
          _campos = campos;
          _dependencias = dependencias;

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

  List<String> _obterValoresAtuaisDoCampo(int idCampo) {
    // DROPDOWN
    if (_dropdownValues.containsKey(idCampo)) {
      final valor = _dropdownValues[idCampo];

      if (valor == null || valor.trim().isEmpty) {
        return [];
      }

      return [valor.trim()];
    }

    // CHECKBOX
    if (_checkboxValues.containsKey(idCampo)) {
      return (_checkboxValues[idCampo] ?? [])
          .map((valor) => valor.trim())
          .where((valor) => valor.isNotEmpty)
          .toList();
    }

    // TEXT, NUMBER, DATE e TIME
    if (_controllers.containsKey(idCampo)) {
      final valor = _controllers[idCampo]?.text.trim() ?? '';

      if (valor.isEmpty) {
        return [];
      }

      return [valor];
    }

    return [];
  }

  bool _campoDeveSerExibido(Map<String, dynamic> campo) {
    final int idCampoFilho = campo['id_campo'] as int;

    // Busca todas as dependências referentes a este campo filho.
    final dependenciasDoCampo =
        _dependencias
            .where(
              (dependencia) => dependencia['id_campo_filho'] == idCampoFilho,
            )
            .toList();

    // Campo sem dependências sempre aparece.
    if (dependenciasDoCampo.isEmpty) {
      return true;
    }

    // Todas as dependências são tratadas como OR.
    //
    // Ou seja:
    // se QUALQUER combinação Campo Pai + Valor
    // estiver satisfeita, o campo filho deve aparecer.
    for (final dependencia in dependenciasDoCampo) {
      final int idCampoPai = dependencia['id_campo_pai'] as int;

      final String valorPermitido = dependencia['valor'].toString().trim();

      final List<String> valoresAtuais = _obterValoresAtuaisDoCampo(idCampoPai);

      final bool dependenciaAtendida = valoresAtuais.any(
        (valorAtual) => valorAtual == valorPermitido,
      );

      if (dependenciaAtendida) {
        return true;
      }
    }

    // Nenhuma das dependências foi satisfeita.
    return false;
  }

  void _limparValorDoCampo(int idCampo, String tipoCampo) {
    switch (tipoCampo) {
      case 'DROPDOWN':
        _dropdownValues[idCampo] = null;
        break;

      case 'CHECKBOX':
        _checkboxValues[idCampo] = [];
        break;

      case 'DATE':
        _controllers[idCampo]?.clear();
        _pickedDates[idCampo] = null;
        break;

      case 'TIME':
        _controllers[idCampo]?.clear();
        _pickedTimes[idCampo] = null;
        break;

      case 'TEXT':
      case 'NUMBER':
      default:
        _controllers[idCampo]?.clear();
        break;
    }
  }

  void _limparCamposDependentes(int idCampoPai, [Set<int>? camposVisitados]) {
    final visitados = camposVisitados ?? <int>{};

    // Evita processamento repetido e possíveis ciclos
    if (!visitados.add(idCampoPai)) {
      return;
    }

    // Descobre quais campos filhos possuem alguma
    // dependência ligada ao campo pai alterado.
    final idsCamposFilhos =
        _dependencias
            .where((dependencia) => dependencia['id_campo_pai'] == idCampoPai)
            .map((dependencia) => dependencia['id_campo_filho'] as int)
            .toSet();

    for (final idCampoFilho in idsCamposFilhos) {
      Map<String, dynamic>? campoFilho;

      for (final campo in _campos) {
        if (campo['id_campo'] == idCampoFilho) {
          campoFilho = Map<String, dynamic>.from(campo);
          break;
        }
      }

      if (campoFilho == null) {
        continue;
      }

      // Depois que o pai mudou, verificamos novamente
      // TODAS as dependências do filho.
      //
      // Se ele ainda puder ser exibido, mantemos seu valor.
      if (_campoDeveSerExibido(campoFilho)) {
        continue;
      }

      final String tipoCampo = campoFilho['tipo_campo'].toString();

      // O filho deixou de ser válido.
      // Portanto, limpamos qualquer resposta que ele possuía.
      _limparValorDoCampo(idCampoFilho, tipoCampo);

      // Como o filho foi limpo, outros campos que
      // dependem dele também precisam ser reavaliados.
      _limparCamposDependentes(idCampoFilho, visitados);
    }
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
          setState(() {
            _limparCamposDependentes(idCampo);
          });
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

              _limparCamposDependentes(idCampo);
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

              _limparCamposDependentes(idCampo);
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
            if (_dropdownValues[idCampo] != newValue) {
              _dropdownValues[idCampo] = newValue;

              _limparCamposDependentes(idCampo);
            }
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

          // Altura ocupada pela barra de navegação do aparelho.
          // Calculada fora do BottomSheet para não interferir
          // no contexto interno do modal.
          final double paddingNavegacao =
              MediaQuery.of(context).viewPadding.bottom;

          await showModalBottomSheet(
            context: context,
            isScrollControlled: true,
            builder: (context) {
              return StatefulBuilder(
                builder: (context, setModalState) {
                  return Padding(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
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

                        // Somente o botão recebe proteção
                        // contra a barra de navegação inferior.
                        Padding(
                          padding: EdgeInsets.only(
                            bottom: 16 + paddingNavegacao,
                          ),
                          child: SizedBox(
                            width: double.infinity,
                            child: ElevatedButton(
                              onPressed: () {
                                setState(() {
                                  _checkboxValues[idCampo] = List<String>.from(
                                    selecionadosTemp,
                                  );

                                  _limparCamposDependentes(idCampo);
                                });

                                Navigator.pop(context);
                              },
                              child: const Text('Confirmar'),
                            ),
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
    // Validação dos campos obrigatórios visíveis
    for (final campo in _campos) {
      if (!_campoDeveSerExibido(campo)) {
        continue;
      }

      final idCampo = campo['id_campo'];
      final tipo = campo['tipo_campo'];

      bool preenchido = false;

      switch (tipo) {
        case 'DROPDOWN':
          preenchido = _dropdownValues[idCampo] != null;
          break;

        case 'CHECKBOX':
          preenchido = (_checkboxValues[idCampo] ?? []).isNotEmpty;
          break;

        default:
          preenchido = _controllers[idCampo]?.text.trim().isNotEmpty ?? false;
          break;
      }

      if (!preenchido) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Preencha o campo "${campo['nome_campo']}".'),
            backgroundColor: Colors.red,
          ),
        );

        return;
      }
    }

    try {
      final respostasMap = <String, dynamic>{};

      for (var campo in _campos) {
        // Não salva campos que estão ocultos pelas regras de dependência
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

        respostasMap[idCampo.toString()] = valorParaSalvar ?? '';
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
