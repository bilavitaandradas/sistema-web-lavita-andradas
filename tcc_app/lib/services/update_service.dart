import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:package_info_plus/package_info_plus.dart';

import 'api_service.dart';

class UpdateService {
  static Future<bool> temAtualizacao() async {
    try {
      final packageInfo = await PackageInfo.fromPlatform();

      final versaoAtual = packageInfo.version;

      final response = await http.get(
        Uri.parse('${ApiService.baseUrl}/check_update.php'),
      );

      if (response.statusCode != 200) {
        return false;
      }

      final dados = jsonDecode(response.body);

      final versaoServidor = dados['version'];

      print('Versão instalada: $versaoAtual');
      print('Versão servidor: $versaoServidor');

      return versaoAtual != versaoServidor;
    } catch (e) {
      print('Erro ao verificar atualização: $e');

      return false;
    }
  }

  static Future<Map<String, dynamic>?> verificarAtualizacao() async {
    try {
      final packageInfo = await PackageInfo.fromPlatform();

      final versaoAtual = packageInfo.version;

      final response = await http.get(
        Uri.parse('${ApiService.baseUrl}/check_update.php'),
      );

      if (response.statusCode != 200) {
        return null;
      }

      final dados = jsonDecode(response.body);

      if (dados['version'] != versaoAtual) {
        return dados;
      }

      return null;
    } catch (e) {
      return null;
    }
  }
}
