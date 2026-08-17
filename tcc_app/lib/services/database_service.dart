import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import 'package:flutter/foundation.dart';

class DatabaseService {
  static final DatabaseService instance = DatabaseService._init();
  static Database? _database;
  DatabaseService._init();

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('tcc_app_local.db');
    return _database!;
  }

  Future<Database> _initDB(String filePath) async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, filePath);

    // Versão 5, adiciona suporte à tabela de dependências dos campos
    return await openDatabase(
      path,
      version: 5,
      onCreate: _createDB,
      onUpgrade: _onUpgrade,
    );
  }

  Future<void> _createDB(Database db, int version) async {
    debugPrint('DB_SERVICE: Executando _createDB... Criando todas as tabelas.');
    await _createTables(db);
  }

  Future<void> _onUpgrade(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 4) {
      await db.execute(
        'ALTER TABLE campos_questionario '
        'ADD COLUMN dependente_de INTEGER',
      );

      await db.execute(
        'ALTER TABLE campos_questionario '
        'ADD COLUMN dependente_valor TEXT',
      );
    }

    if (oldVersion < 5) {
      await db.execute('''
        CREATE TABLE IF NOT EXISTS dependencias_campos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_campo_filho INTEGER NOT NULL,
        id_campo_pai INTEGER NOT NULL,
        valor TEXT NOT NULL,
        FOREIGN KEY (id_campo_filho)
        REFERENCES campos_questionario (id_campo)
        ON DELETE CASCADE
    )
  ''');
    }

    await _createTables(db);
  }

  Future<void> _createTables(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS lancamentos_offline (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_lancamento TEXT NOT NULL UNIQUE,
        id_questionario INTEGER NOT NULL,
        id_usuario INTEGER NOT NULL,
        respostas TEXT NOT NULL,
        criado_em_local TEXT NOT NULL,
        sincronizado INTEGER NOT NULL DEFAULT 0
      )
    ''');

    await db.execute('''
      CREATE TABLE IF NOT EXISTS questionarios (
        id_questionario INTEGER PRIMARY KEY,
        nome_questionario TEXT NOT NULL,
        descricao_questionario TEXT
      )
    ''');

    await db.execute('''
      CREATE TABLE IF NOT EXISTS campos_questionario (
        id_campo INTEGER PRIMARY KEY,
        id_questionario INTEGER NOT NULL,
        nome_campo TEXT NOT NULL,
        tipo_campo TEXT NOT NULL,
        dependente_de INTEGER,
        dependente_valor TEXT,
        opcoes TEXT,
        FOREIGN KEY (id_questionario) REFERENCES questionarios (id_questionario) ON DELETE CASCADE
      )
    ''');

    await db.execute('''
      CREATE TABLE IF NOT EXISTS dependencias_campos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_campo_filho INTEGER NOT NULL,
        id_campo_pai INTEGER NOT NULL,
        valor TEXT NOT NULL,
        FOREIGN KEY (id_campo_filho)
        REFERENCES campos_questionario (id_campo)
        ON DELETE CASCADE
  )
''');
    debugPrint(
      'DB_SERVICE: Tabelas (lancamentos_offline, questionarios, campos_questionario) verificadas/criadas.',
    );
  }

  // --- MÉTODO PARA SALVAR O CACHE ---
  Future<void> salvarCacheDeSincronizacao(Map<String, dynamic> data) async {
    final db = await instance.database;
    final questionarios = data['questionarios'] as List;
    final campos = data['campos'] as List;
    final dependencias = data['dependencias'] as List? ?? [];

    await db.transaction((txn) async {
      final batch = txn.batch();
      batch.delete('dependencias_campos');
      batch.delete('campos_questionario');
      batch.delete('questionarios');

      for (var questionario in questionarios) {
        batch.insert(
          'questionarios',
          questionario as Map<String, dynamic>,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
      for (var campo in campos) {
        batch.insert(
          'campos_questionario',
          campo as Map<String, dynamic>,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
      for (var dependencia in dependencias) {
        batch.insert(
          'dependencias_campos',
          dependencia as Map<String, dynamic>,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
      await batch.commit(noResult: true);
    });
    debugPrint(
      'DB_SERVICE: Cache de questionários, campos e dependências atualizado.',
    );
  }

  // --- MÉTODOS DE LEITURA DO CACHE LOCAL ---
  Future<List<Map<String, dynamic>>> getQuestionariosLocais() async {
    final db = await instance.database;
    return await db.query('questionarios', orderBy: 'nome_questionario ASC');
  }

  Future<List<Map<String, dynamic>>> getCamposDoQuestionarioLocal(
    int idQuestionario,
  ) async {
    final db = await instance.database;
    return await db.query(
      'campos_questionario',
      where: 'id_questionario = ?',
      whereArgs: [idQuestionario],
      orderBy: 'id_campo ASC',
    );
  }

  Future<List<Map<String, dynamic>>> getDependenciasDoCampoLocal(
    int idCampoFilho,
  ) async {
    final db = await instance.database;

    return await db.query(
      'dependencias_campos',
      where: 'id_campo_filho = ?',
      whereArgs: [idCampoFilho],
      orderBy: 'id_campo_pai ASC, valor ASC',
    );
  }

  Future<List<Map<String, dynamic>>> getDependenciasDoQuestionarioLocal(
    int idQuestionario,
  ) async {
    final db = await instance.database;

    return await db.rawQuery(
      '''
    SELECT
      dc.id_campo_filho,
      dc.id_campo_pai,
      dc.valor
    FROM dependencias_campos dc
    INNER JOIN campos_questionario c
      ON c.id_campo = dc.id_campo_filho
    WHERE c.id_questionario = ?
    ORDER BY
      dc.id_campo_filho ASC,
      dc.id_campo_pai ASC,
      dc.valor ASC
    ''',
      [idQuestionario],
    );
  }

  // --- MÉTODOS PARA GERENCIAR LANÇAMENTOS OFFLINE ---
  Future<int> countLancamentosPendentes() async {
    final db = await instance.database;
    final result = await db.rawQuery(
      'SELECT COUNT(*) FROM lancamentos_offline WHERE sincronizado = 0',
    );
    return Sqflite.firstIntValue(result) ?? 0;
  }

  Future<void> createLancamento(Map<String, dynamic> lancamento) async {
    final db = await instance.database;
    await db.insert(
      'lancamentos_offline',
      lancamento,
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<List<Map<String, dynamic>>> getLancamentosPendentes() async {
    final db = await instance.database;
    return await db.query(
      'lancamentos_offline',
      where: 'sincronizado = ?',
      whereArgs: [0],
      orderBy: 'criado_em_local DESC',
    );
  }

  Future<void> deleteLancamento(String idLancamento) async {
    final db = await instance.database;
    await db.delete(
      'lancamentos_offline',
      where: 'id_lancamento = ?',
      whereArgs: [idLancamento],
    );
  }

  Future<void> marcarComoSincronizado(List<String> idsLancamentos) async {
    final db = await instance.database;
    if (idsLancamentos.isEmpty) return;
    final placeholders = List.generate(
      idsLancamentos.length,
      (_) => '?',
    ).join(',');
    await db.rawUpdate(
      'UPDATE lancamentos_offline SET sincronizado = 1 WHERE id_lancamento IN ($placeholders)',
      idsLancamentos,
    );
  }

  Future<int> clearLancamentosPendentes() async {
    final db = await instance.database;
    final count = await db.delete(
      'lancamentos_offline',
      where: 'sincronizado = ?',
      whereArgs: [0],
    );
    return count;
  }
}
