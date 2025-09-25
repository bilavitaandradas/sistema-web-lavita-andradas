import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = 'http://192.168.172.231/TCC/app/api'; // Use 10.0.2.2 para emulador ou o IP da sua máquina para celular físico

  // --- MÉTODO DE LOGIN ---
  static Future<Map<String, dynamic>> login(String username, String password) async {
    final url = Uri.parse('$baseUrl/auth.php');
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'username': username, 'password': password}),
    );
    if (response.statusCode != 200) {
      throw Exception('Falha ao conectar ao servidor. Código: ${response.statusCode}');
    }
    return jsonDecode(response.body);
  }

  // --- NOVO MÉTODO PRINCIPAL PARA O "SYNC DOWN" ---
  // Busca todos os dados necessários para o app funcionar offline.
  static Future<Map<String, dynamic>> getDadosParaSincronizar(String token) async {
    final url = Uri.parse('$baseUrl/get_dados_app.php');
    final response = await http.get(
      url,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode != 200) {
      throw Exception('Falha ao buscar dados para sincronização. Código: ${response.statusCode}');
    }
    
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      // Retorna o Map completo com as chaves 'questionarios' e 'campos'
      return data;
    } else {
      throw Exception(data['message'] ?? 'Erro ao buscar dados para sincronização');
    }
  }
  
  // --- MÉTODO PRINCIPAL PARA O "SYNC UP" ---
  // Envia os lançamentos feitos offline para o servidor.
  static Future<Map<String, dynamic>> sincronizarLancamentos(String token, List<Map<String, dynamic>> lancamentos) async {
    final url = Uri.parse('$baseUrl/sincronizar.php');
    final response = await http.post(
      url,
      headers: {
        'Content-Type': 'application/json; charset=UTF-8',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode(lancamentos),
    );
    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Falha ao sincronizar dados. Código: ${response.statusCode}');
    }
  }

  // --- MÉTODOS ANTIGOS (Podem ser removidos ou mantidos para referência) ---

  static Future<List<dynamic>> getQuestionarios(String token) async {
    final url = Uri.parse('$baseUrl/questionarios.php');
    final response = await http.get(
      url,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );
    if (response.statusCode != 200) {
      throw Exception('Falha ao conectar ao servidor. Código: ${response.statusCode}');
    }
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return data['questionarios'];
    } else {
      throw Exception(data['message'] ?? 'Erro ao buscar questionários');
    }
  }

  static Future<List<dynamic>> getCamposDoQuestionario(String token, int idQuestionario) async {
    final url = Uri.parse('$baseUrl/get_campos.php?id_questionario=$idQuestionario');
    final response = await http.get(
      url,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );
    if (response.statusCode != 200) {
      throw Exception('Falha ao conectar ao servidor. Código: ${response.statusCode}');
    }
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return data['campos'];
    } else {
      throw Exception(data['message'] ?? 'Erro ao buscar os campos do questionário');
    }
  }

  static Future<List<dynamic>> getMeusLancamentos(String token) async {
    final url = Uri.parse('$baseUrl/meus_lancamentos.php');
    final response = await http.get(
      url,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode != 200) {
      throw Exception('Falha ao conectar ao servidor. Código: ${response.statusCode}');
    }
    final data = jsonDecode(response.body);
    if (data['success'] == true) {
      return data['lancamentos']; // Retorna diretamente a lista
    } else {
      throw Exception(data['message'] ?? 'Erro ao buscar seus lançamentos');
    }
  }
}