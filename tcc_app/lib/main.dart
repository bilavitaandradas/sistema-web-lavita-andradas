import 'package:flutter/material.dart';
import 'screens/auth_check_screen.dart';
import 'services/database_service.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
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
      
      // --- 2. CONFIGURAÇÕES DE IDIOMA ADICIONADAS AQUI ---
      locale: Locale('pt', 'BR'),
      localizationsDelegates: [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: [
        Locale('pt', 'BR'),
      ],
      // ----------------------------------------------------

      home: AuthCheckScreen(),
    );
  }
}