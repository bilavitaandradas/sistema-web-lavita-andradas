import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AuthService {
  static final AuthService instance = AuthService._init();
  AuthService._init();

  // A instância do storage vive AQUI, e apenas aqui.
  final _storage = const FlutterSecureStorage();

  // Salva todos os dados importantes da sessão
  Future<void> saveSession({
    required String token, 
    required String userId,
    required String nome,
    required String username,
  }) async {
    await _storage.write(key: 'auth_token', value: token);
    await _storage.write(key: 'user_id', value: userId);
    await _storage.write(key: 'user_nome', value: nome);
    await _storage.write(key: 'user_username', value: username);
  }

  // Busca o token atual
  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }

  // Busca o ID do usuário
  Future<String?> getUserId() async {
    return await _storage.read(key: 'user_id');
  }

  // Busca os dados do usuário salvos para exibição
  Future<Map<String, String?>> getUserData() async {
    final nome = await _storage.read(key: 'user_nome');
    final username = await _storage.read(key: 'user_username');
    return {'nome': nome, 'username': username};
  }

  // Apaga todos os dados da sessão (faz o logout)
  Future<void> deleteSession() async {
    await _storage.deleteAll();
  }
}