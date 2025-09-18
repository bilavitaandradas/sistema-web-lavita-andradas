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
  
  // Mapa para controllers de campos de texto (incluindo os que exibem data/hora)
  final Map<int, TextEditingController> _controllers = {};
  // Mapa para valores de dropdowns
  final Map<int, String?> _dropdownValues = {};
  
  // Mapas para guardar os valores brutos de Data e Hora, separados da exibição
  final Map<int, DateTime?> _pickedDates = {};
  final Map<int, TimeOfDay?> _pickedTimes = {};

  @override
  void initState() {
    super.initState();
    _fetchCamposFromLocalDB();
  }

  // A função agora busca os campos do banco de dados LOCAL (SQLite)
  Future<void> _fetchCamposFromLocalDB() async {
    try {
      final campos = await DatabaseService.instance.getCamposDoQuestionarioLocal(widget.idQuestionario);
      if (mounted) {
        setState(() {
          _campos = campos;
          for (var campo in _campos) {
            final idCampo = campo['id_campo'] as int;
            final tipoCampo = campo['tipo_campo'];
            if (tipoCampo == 'DROPDOWN') {
              _dropdownValues[idCampo] = null;
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.nomeQuestionario),
      ),
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
      // Usamos SingleChildScrollView com Column para evitar problemas de layout
      // com o teclado aparecendo. Ou podemos usar um ListView.
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
      itemCount: _campos.length,
      itemBuilder: (context, index) {
        return _buildFormField(_campos[index]);
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
      default:
        return _buildTextField(idCampo, nomeCampo);
    }
  }

  Widget _buildTextField(int idCampo, String nomeCampo, {bool isNumber = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20.0),
      child: TextFormField(
        controller: _controllers[idCampo],
        decoration: InputDecoration(
          labelText: nomeCampo,
          border: const OutlineInputBorder(),
        ),
        keyboardType: isNumber ? const TextInputType.numberWithOptions(decimal: true) : TextInputType.text,
        inputFormatters: isNumber 
            ? [FilteringTextInputFormatter.allow(RegExp(r'[\d.,]'))] 
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
              _controllers[idCampo]!.text = DateFormat('dd/MM/yyyy').format(pickedDate);
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
                data: MediaQuery.of(context).copyWith(alwaysUse24HourFormat: true),
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

  Widget _buildDropdownField(int idCampo, String nomeCampo, List<String> opcoes) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20.0),
      child: DropdownButtonFormField<String>(
        value: _dropdownValues[idCampo],
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
        items: opcoes.map<DropdownMenuItem<String>>((String valor) {
          return DropdownMenuItem<String>(
            value: valor,
            child: Text(valor),
          );
        }).toList(),
      ),
    );
  }
  
  Future<void> _salvarOffline() async {
    try {
      final respostasMap = <String, dynamic>{};
      
      for (var campo in _campos) {
        final idCampo = campo['id_campo'] as int;
        final tipoCampo = campo['tipo_campo'];
        String? valorParaSalvar;

        switch (tipoCampo) {
          case 'DATE':
            if (_pickedDates[idCampo] != null) {
              valorParaSalvar = DateFormat('yyyy-MM-dd').format(_pickedDates[idCampo]!);
            }
            break;
          case 'TIME':
             if (_pickedTimes[idCampo] != null) {
              final hour = _pickedTimes[idCampo]!.hour.toString().padLeft(2, '0');
              final minute = _pickedTimes[idCampo]!.minute.toString().padLeft(2, '0');
              valorParaSalvar = '$hour:$minute';
            }
            break;
          case 'DROPDOWN':
            valorParaSalvar = _dropdownValues[idCampo];
            break;
          default: // TEXT, NUMBER
            valorParaSalvar = _controllers[idCampo]?.text;
            break;
        }

        if (valorParaSalvar == null || valorParaSalvar.isEmpty) {
          throw Exception('O campo "${campo['nome_campo']}" é obrigatório.');
        }
        respostasMap[idCampo.toString()] = valorParaSalvar;
      }

      final userIdString = await AuthService.instance.getUserId();
      if (userIdString == null) throw Exception('ID do usuário não encontrado.');
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
          const SnackBar(content: Text('Lançamento salvo com sucesso offline!'), backgroundColor: Colors.green),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erro ao salvar: ${e.toString().replaceAll("Exception: ", "")}'), backgroundColor: Colors.red),
        );
      }
    }
  }
}