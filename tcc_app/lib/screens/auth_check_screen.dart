import 'package:flutter/material.dart';
import 'home_screen.dart';
import 'login_screen.dart';
import '../services/auth_service.dart'; // Importamos o AuthService

class AuthCheckScreen extends StatefulWidget {
  const AuthCheckScreen({super.key});

  @override
  State<AuthCheckScreen> createState() => _AuthCheckScreenState();
}

class _AuthCheckScreenState extends State<AuthCheckScreen> {
  @override
  void initState() {
    super.initState();
    _checkLoginStatus();
  }

  Future<void> _checkLoginStatus() async {
    await Future.delayed(const Duration(seconds: 1));

    // Verifica se o token existe
    String? token = await AuthService.instance.getToken();

    if (!mounted) return;

    if (token != null) {
      // --- PONTO DA ALTERAÇÃO ---
      // Se o token existe, também buscamos os dados do usuário salvos
      final userData = await AuthService.instance.getUserData();
      final nome =
          userData['nome'] ??
          'Usuário'; // Usa o nome salvo, ou 'Usuário' como fallback

      // Navega para a HomeScreen, agora com o nome correto
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => HomeScreen(nome: nome)),
      );
      // --------------------------
    } else {
      // Se não encontrou um token, navega para a LoginScreen
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(body: Center(child: CircularProgressIndicator()));
  }
}
