<?php
InsertRecord('accountsection',array('sectionid'),array(10),array('sectionid','sectionname'),array(10,'Assets'), $db);
InsertRecord('accountsection',array('sectionid'),array(20),array('sectionid','sectionname'),array(20,'Liabilities'), $db);
InsertRecord('accountsection',array('sectionid'),array(30),array('sectionid','sectionname'),array(30,'Income'), $db);
InsertRecord('accountsection',array('sectionid'),array(40),array('sectionid','sectionname'),array(40,'Costs'), $db);
InsertRecord('accountgroups',array('groupname'),array('Grupo 1: financiaci'),array('groupname','sectioninaccounts','pandl','sequenceintb','parentgroupname'),array('Grupo 1: financiaci','20','0','1000',''), $db);
InsertRecord('accountgroups',array('groupname'),array('Grupo 2: inmovilizado'),array('groupname','sectioninaccounts','pandl','sequenceintb','parentgroupname'),array('Grupo 2: inmovilizado','20','0','2000',''), $db);
InsertRecord('accountgroups',array('groupname'),array('Grupo 3: existencias'),array('groupname','sectioninaccounts','pandl','sequenceintb','parentgroupname'),array('Grupo 3: existencias','20','0','3000',''), $db);
InsertRecord('accountgroups',array('groupname'),array('Grupo 4: acreedores y deudores'),array('groupname','sectioninaccounts','pandl','sequenceintb','parentgroupname'),array('Grupo 4: acreedores y deudores','20','0','4000',''), $db);
InsertRecord('accountgroups',array('groupname'),array('Grupo 5: cuentas financieras'),array('groupname','sectioninaccounts','pandl','sequenceintb','parentgroupname'),array('Grupo 5: cuentas financieras','20','0','5000',''), $db);
InsertRecord('accountgroups',array('groupname'),array('Grupo 6: compras y gastos'),array('groupname','sectioninaccounts','pandl','sequenceintb','parentgroupname'),array('Grupo 6: compras y gastos','20','0','6000',''), $db);
InsertRecord('accountgroups',array('groupname'),array('Grupo 7: ventas e ingresos'),array('groupname','sectioninaccounts','pandl','sequenceintb','parentgroupname'),array('Grupo 7: ventas e ingresos','20','0','7000',''), $db);
InsertRecord('chartmaster',array('accountcaode'),array('100000000'),array('accountcode','accountname','group_'),array('100000000','Capital','Grupo 1: financiaci'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('110000000'),array('accountcode','accountname','group_'),array('110000000','Reservas','Grupo 1: financiaci'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('120000000'),array('accountcode','accountname','group_'),array('120000000','Resultados pendientes de aplicaci','Grupo 1: financiaci'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('130000000'),array('accountcode','accountname','group_'),array('130000000','Ingresos a distribuir en varios ejercicios','Grupo 1: financiaci'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('140000000'),array('accountcode','accountname','group_'),array('140000000','Provisiones para riesgos y gastos','Grupo 1: financiaci'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('150000000'),array('accountcode','accountname','group_'),array('150000000','Empr','Grupo 1: financiaci'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('160000000'),array('accountcode','accountname','group_'),array('160000000','Deudas a largo plazo con empresas del grupo y asoc','Grupo 1: financiaci'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('170000000'),array('accountcode','accountname','group_'),array('170000000','Deudas a largo plazo por prestamos recibidos y otr','Grupo 1: financiaci'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('180000000'),array('accountcode','accountname','group_'),array('180000000','Fianzas y dep','Grupo 1: financiaci'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('190000000'),array('accountcode','accountname','group_'),array('190000000','Situaciones transitorias de financiaci','Grupo 1: financiaci'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('200000000'),array('accountcode','accountname','group_'),array('200000000','Gastos de establecimiento','Grupo 2: inmovilizado'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('210000000'),array('accountcode','accountname','group_'),array('210000000','Inmovilizaciones inmateriales','Grupo 2: inmovilizado'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('220000000'),array('accountcode','accountname','group_'),array('220000000','Inmovilizaciones materiales','Grupo 2: inmovilizado'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('230000000'),array('accountcode','accountname','group_'),array('230000000','Inmovilizaciones materiales en curso','Grupo 2: inmovilizado'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('240000000'),array('accountcode','accountname','group_'),array('240000000','Inversiones financieras en empresas del grupo y as','Grupo 2: inmovilizado'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('250000000'),array('accountcode','accountname','group_'),array('250000000','Otras inversiones financieras permanentes','Grupo 2: inmovilizado'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('260000000'),array('accountcode','accountname','group_'),array('260000000','Fianzas y dep','Grupo 2: inmovilizado'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('270000000'),array('accountcode','accountname','group_'),array('270000000','Gastos a distribuir en varios ejercicios','Grupo 2: inmovilizado'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('280000000'),array('accountcode','accountname','group_'),array('280000000','Amortizaci','Grupo 2: inmovilizado'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('290000000'),array('accountcode','accountname','group_'),array('290000000','Provisiones de inmovilizado','Grupo 2: inmovilizado'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('300000000'),array('accountcode','accountname','group_'),array('300000000','Comerciales','Grupo 3: existencias'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('310000000'),array('accountcode','accountname','group_'),array('310000000','Materias primas','Grupo 3: existencias'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('320000000'),array('accountcode','accountname','group_'),array('320000000','Otros aprovisionamientos','Grupo 3: existencias'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('330000000'),array('accountcode','accountname','group_'),array('330000000','Productos en curso','Grupo 3: existencias'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('340000000'),array('accountcode','accountname','group_'),array('340000000','Productos semiterminados','Grupo 3: existencias'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('350000000'),array('accountcode','accountname','group_'),array('350000000','Productos terminados','Grupo 3: existencias'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('360000000'),array('accountcode','accountname','group_'),array('360000000','Subproductos, residuos y materiales recuperados','Grupo 3: existencias'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('390000000'),array('accountcode','accountname','group_'),array('390000000','Provisiones por depreciaci','Grupo 3: existencias'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('400000000'),array('accountcode','accountname','group_'),array('400000000','Proveedores','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('410000000'),array('accountcode','accountname','group_'),array('410000000','Acreedores varios','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('430000000'),array('accountcode','accountname','group_'),array('430000000','Clientes','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('431000000'),array('accountcode','accountname','group_'),array('431000000','Clientes, efectos comerciales a cobrar','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('440000000'),array('accountcode','accountname','group_'),array('440000000','Deudores varios','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('460000000'),array('accountcode','accountname','group_'),array('460000000','Personal','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('470000000'),array('accountcode','accountname','group_'),array('470000000','Administraciones p','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('472000000'),array('accountcode','accountname','group_'),array('472000000','Hacienda P','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('472000001'),array('accountcode','accountname','group_'),array('472000001','IVA soportado 4%','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('472000002'),array('accountcode','accountname','group_'),array('472000002','IVA soportado 7%','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('472000003'),array('accountcode','accountname','group_'),array('472000003','IVA soportado 16%','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('473000000'),array('accountcode','accountname','group_'),array('473000000','Hacienda P','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('475100000'),array('accountcode','accountname','group_'),array('475100000','Hacienda P','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('477000000'),array('accountcode','accountname','group_'),array('477000000','Hacienda P','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('477000001'),array('accountcode','accountname','group_'),array('477000001','IVA repercutido 4%','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('477000002'),array('accountcode','accountname','group_'),array('477000002','IVA repercutido 7%','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('477000003'),array('accountcode','accountname','group_'),array('477000003','IVA repercutido 16%','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('480000000'),array('accountcode','accountname','group_'),array('480000000','Ajustes por periodificaci','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('490000000'),array('accountcode','accountname','group_'),array('490000000','Provisiones por operaciones de tr','Grupo 4: acreedores y deudores'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('500000000'),array('accountcode','accountname','group_'),array('500000000','Empr','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('510000000'),array('accountcode','accountname','group_'),array('510000000','Deudas a corto plazo con empresas del grupo y asoc','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('520000000'),array('accountcode','accountname','group_'),array('520000000','Deudas a corto plazo por pr','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('530000000'),array('accountcode','accountname','group_'),array('530000000','Inversiones financieras a corto plazo en empresas','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('540000000'),array('accountcode','accountname','group_'),array('540000000','Otras inversiones financieras temporales','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('550000000'),array('accountcode','accountname','group_'),array('550000000','Otras cuentas no bancarias','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('560000000'),array('accountcode','accountname','group_'),array('560000000','Fianzas y dep','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('569000000'),array('accountcode','accountname','group_'),array('569000000','Tesorer','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('570000000'),array('accountcode','accountname','group_'),array('570000000','Caja, euros','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('571000000'),array('accountcode','accountname','group_'),array('571000000','Caja, moneda extranjera','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('572000000'),array('accountcode','accountname','group_'),array('572000000','Bancos e instituciones de cr','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('573000000'),array('accountcode','accountname','group_'),array('573000000','Bancos e instituciones de cr','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('574000000'),array('accountcode','accountname','group_'),array('574000000','Bancos e instituciones de cr','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('575000000'),array('accountcode','accountname','group_'),array('575000000','Bancos e instituciones de cr','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('580000000'),array('accountcode','accountname','group_'),array('580000000','Ajustes por periodificaci','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('590000000'),array('accountcode','accountname','group_'),array('590000000','Provisiones financieras','Grupo 5: cuentas financieras'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('600000000'),array('accountcode','accountname','group_'),array('600000000','Compras','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('608000000'),array('accountcode','accountname','group_'),array('608000000','Devoluciones de compras y operaciones similares','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('610000000'),array('accountcode','accountname','group_'),array('610000000','Variaci','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('620000000'),array('accountcode','accountname','group_'),array('620000000','Servicios exteriores','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('630000000'),array('accountcode','accountname','group_'),array('630000000','Tributos','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('640000000'),array('accountcode','accountname','group_'),array('640000000','Gastos de personal','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('650000000'),array('accountcode','accountname','group_'),array('650000000','Otros gastos de gesti','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('660000000'),array('accountcode','accountname','group_'),array('660000000','Gastos financieros','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('665000000'),array('accountcode','accountname','group_'),array('665000000','Descuentos sobre ventas por pronto pago','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('668000000'),array('accountcode','accountname','group_'),array('668000000','Diferencias negativas de cambio','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('670000000'),array('accountcode','accountname','group_'),array('670000000','P','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('680000000'),array('accountcode','accountname','group_'),array('680000000','Dotaciones para amortizaciones','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('690000000'),array('accountcode','accountname','group_'),array('690000000','Dotaciones a las provisiones','Grupo 6: compras y gastos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('700000000'),array('accountcode','accountname','group_'),array('700000000','Ventas de mercader','Grupo 7: ventas e ingresos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('708000000'),array('accountcode','accountname','group_'),array('708000000','Devoluciones de ventas y operaciones similares','Grupo 7: ventas e ingresos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('710000000'),array('accountcode','accountname','group_'),array('710000000','Variaci','Grupo 7: ventas e ingresos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('730000000'),array('accountcode','accountname','group_'),array('730000000','Trabajos realizados para la empresa','Grupo 7: ventas e ingresos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('740000000'),array('accountcode','accountname','group_'),array('740000000','Subvenciones a la explotaci','Grupo 7: ventas e ingresos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('750000000'),array('accountcode','accountname','group_'),array('750000000','Otros ingresos de gesti','Grupo 7: ventas e ingresos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('760000000'),array('accountcode','accountname','group_'),array('760000000','Ingresos financieros','Grupo 7: ventas e ingresos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('765000000'),array('accountcode','accountname','group_'),array('765000000','Descuentos sobre compras por pronto pago','Grupo 7: ventas e ingresos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('768000000'),array('accountcode','accountname','group_'),array('768000000','Diferencias positivas de cambio','Grupo 7: ventas e ingresos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('770000000'),array('accountcode','accountname','group_'),array('770000000','Beneficios procedentes de inmovilizados e ingresos','Grupo 7: ventas e ingresos'), $db);
InsertRecord('chartmaster',array('accountcaode'),array('790000000'),array('accountcode','accountname','group_'),array('790000000','Excesos y aplicaciones de provisiones','Grupo 7: ventas e ingresos'), $db);
?>