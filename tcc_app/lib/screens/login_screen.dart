import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _userController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  
  // REMOVEMOS a instância do _storage daqui
  // final _storage = const FlutterSecureStorage(); 
  
  bool _obscurePassword = true;
  bool _keepConnected = true; 
  bool _loading = false;

  Future<void> _login() async {
    FocusScope.of(context).unfocus();
    final username = _userController.text.trim();
    final password = _passwordController.text;

    if (username.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Preencha usuário e senha')));
      return;
    }

    setState(() { _loading = true; });

    try {
      final response = await ApiService.login(username, password);

      if (response['success'] == true && mounted) {
        final token = response['token'];
        final user = response['user'];

        if (_keepConnected) {
          // Usamos o AuthService para salvar tudo
          await AuthService.instance.saveSession(
            token: token,
            userId: user['id'].toString(),
            nome: user['nome'],
            username: user['username'],
          );
        }
        
        Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => HomeScreen(nome: user['nome'])));
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(response['message'] ?? 'Erro no login')));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erro de conexão: ${e.toString()}')));
      }
    } finally {
      if (mounted) {
        setState(() { _loading = false; });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[100],
      body: Center(
        child: SingleChildScrollView(
          child: Card(
            elevation: 4,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            margin: const EdgeInsets.symmetric(horizontal: 24),
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Image.asset('assets/images/logo.png', height: 80),
                  const SizedBox(height: 20),
                  const Text('Faça seu Login', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 20),
                  TextField(
                    controller: _userController,
                    decoration: const InputDecoration(labelText: 'Usuário', border: OutlineInputBorder()),
                    textInputAction: TextInputAction.next,
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    decoration: InputDecoration(
                      labelText: 'Senha',
                      border: const OutlineInputBorder(),
                      suffixIcon: IconButton(
                        icon: Icon(_obscurePassword ? Icons.visibility_off : Icons.visibility),
                        onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                      ),
                    ),
                    onSubmitted: (_) => _login(),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Checkbox(
                        value: _keepConnected,
                        onChanged: (v) => setState(() => _keepConnected = v ?? false),
                      ),
                      const Text("Continuar Conectado"),
                    ],
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        textStyle: const TextStyle(fontSize: 18)
                      ),
                      onPressed: _loading ? null : _login,
                      child: _loading 
                        ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 3,) 
                        : const Text("Entrar"),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}