# 00 — Visão

## Nome de trabalho
**MedTurno**

## Uma frase
Substituir Excel + grupo de WhatsApp por um app onde o gestor monta a escala mensal de plantão médico e os médicos confirmam, trocam e anunciam plantões sem sair do sistema.

## Cliente inicial
Thallys — médico gestor de escala de **2 hospitais** (Santa Maria e São Gabriel), com equipe de **~100 médicos plantonistas**, podendo administrar mais hospitais.

## Situação hoje (o que substituímos)
- Escala montada em **planilha Excel** e distribuída manualmente
- Trocas de plantão negociadas no **grupo de WhatsApp** dos médicos
- Sem histórico rastreável, sem confirmação formal, sem visão consolidada do médico que trabalha em mais de um hospital

## Dor real (por que dói)
- Gestor gasta tempo montando/atualizando planilhas e respondendo trocas no WhatsApp
- Médico não sabe com certeza se o plantão dele foi ou não trocado (WhatsApp perde histórico)
- Não há registro formal de quem aceitou/recusou plantão — fonte constante de mal-entendido

## Proposta de valor (o que o app entrega)
1. **Gestor** monta a escala mensal em interface visual (arrastar médico pro dia/turno)
2. **Médico** vê a própria escala consolidada de todos os hospitais em um só lugar
3. **Troca direta** entre médicos (com aceite do receptor + aprovação do gestor)
4. **Mural de plantões disponíveis** — médico anuncia, qualquer colega do mesmo quadro pega
5. **Notificações** internas (v1) e por e-mail (v1) de tudo que envolve o médico
6. **Histórico** de toda troca e alteração para o gestor poder auditar
7. **Faturamento**: gestor define o valor de cada plantão e o app calcula quanto cada médico recebe no mês (relatório por hospital e consolidado)

## Concorrente honesto
**Excel + WhatsApp.** A barra é baixa. Se for melhor que isso em usabilidade e confiabilidade, ganha. Não precisa competir com iClinic ou Feegow no v1.

## O que **não** é este produto (v1)
- Não é prontuário eletrônico
- Não é sistema de pagamento (calcula e reporta o faturamento, mas não paga ninguém, não emite recibo/NF, não desconta imposto)
- Não é folha de ponto biométrica
- Não é ERP hospitalar
- Não faz integração com sistema do hospital (Tasy/MV)
- Não emite recibo, RPA, NF
- Não gerencia paciente
- Não é agenda de consulta ambulatorial
