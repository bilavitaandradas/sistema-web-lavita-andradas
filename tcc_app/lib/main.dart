import 'package:flutter/material.dart';
import 'screens/auth_check_screen.dart';
import 'services/database_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Adiciona um print para sabermos que a inicialização está sendo chamada
  debugPrint("MAIN: Solicitando inicialização do banco de dados...");
  await DatabaseService.instance.database;
  debugPrint("MAIN: Banco de dados pronto.");
  
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return const MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'tcc_app',
      home: AuthCheckScreen(),
    );
  }
}