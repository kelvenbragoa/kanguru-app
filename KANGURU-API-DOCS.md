# 🚚 KANGURU API - Documentação Completa

## Base URL
```
http://127.0.0.1:8001/api/v1
```

## 📋 Endpoints da API

### 🔐 Autenticação

#### 1. Registro de Usuário
```http
POST /auth/register
Content-Type: application/json

{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "12345678",
  "password_confirmation": "12345678",
  "role_id": 3,
  "phone": "(11) 99999-9999",
  "document": "123.456.789-00",
  "address": "Rua A, 123",
  "city": "São Paulo",
  "state": "SP",
  "postal_code": "01000-000"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "role": {
        "id": 3,
        "name": "customer",
        "display_name": "Cliente"
      },
      "profile": {
        "phone": "(11) 99999-9999",
        "document": "123.456.789-00"
      }
    },
    "token": "1|abcd1234...",
    "token_type": "Bearer"
  }
}
```

#### 2. Login
```http
POST /auth/login
Content-Type: application/json

{
  "email": "joao@example.com",
  "password": "12345678"
}
```

#### 3. Perfil do Usuário
```http
GET /auth/me
Authorization: Bearer {token}
```

#### 4. Atualizar Perfil
```http
PUT /auth/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "João Silva Santos",
  "phone": "(11) 88888-8888"
}
```

#### 5. Logout
```http
POST /auth/logout
Authorization: Bearer {token}
```

### 📦 Pedidos

#### 1. Listar Pedidos
```http
GET /orders
Authorization: Bearer {token}

# Filtros opcionais:
GET /orders?status=pending
GET /orders?type=Delivery
```

#### 2. Criar Pedido
```http
POST /orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "order_type_id": 1,
  "origin": "Rua A, 123 - Centro",
  "destination": "Rua B, 456 - Vila Nova",
  "delivery_fee": 15.50,
  "weight": 2.5,
  "notes": "Entregar no portão",
  "scheduled_at": "2025-09-10 14:00:00",
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "price": 25.90
    }
  ]
}
```

#### 3. Detalhes do Pedido
```http
GET /orders/{id}
Authorization: Bearer {token}
```

#### 4. Atualizar Status do Pedido
```http
PUT /orders/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "order_status_id": 3,
  "notes": "Motorista a caminho"
}
```

### 📍 Rastreamento

#### 1. Rastreamento Público (sem token)
```http
GET /tracking/{orderCode}

# Exemplo:
GET /tracking/KNG-1693434123-5678
```

#### 2. Atualizar Status e Localização
```http
PUT /tracking/orders/{orderId}/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "order_status_id": 5,
  "description": "Produto coletado",
  "local": "Loja ABC - Centro",
  "latitude": -23.5505,
  "longitude": -46.6333
}
```

#### 3. Localização do Motorista
```http
GET /tracking/orders/{orderId}/location
Authorization: Bearer {token}
```

#### 4. Pedidos do Motorista
```http
GET /tracking/driver/orders
Authorization: Bearer {token}
```

### 🚚 Veículos

#### 1. Listar Veículos
```http
GET /vehicles
Authorization: Bearer {token}

# Filtros:
GET /vehicles?status=available
GET /vehicles?type=Moto
```

#### 2. Veículos Disponíveis
```http
GET /vehicles-available
Authorization: Bearer {token}

# Filtros:
GET /vehicles-available?min_capacity=100
GET /vehicles-available?vehicle_type_id=1
```

#### 3. Criar Veículo
```http
POST /vehicles
Authorization: Bearer {token}
Content-Type: application/json

{
  "license_plate_number": "ABC-1234",
  "model": "Honda CG 160",
  "color": "Vermelha",
  "vehicle_type_id": 1,
  "capacity": 30.00,
  "year": 2023
}
```

### 🏪 Lojas

#### 1. Listar Lojas (público)
```http
GET /shops

# Com busca:
GET /shops?search=pizza
```

#### 2. Detalhes da Loja (público)
```http
GET /shops/{id}
```

### 🛍️ Produtos

#### 1. Listar Produtos (público)
```http
GET /products

# Filtros:
GET /products?shop_id=1
GET /products?category_id=1
GET /products?search=pizza
GET /products?active=true
```

#### 2. Produtos por Categoria (público)
```http
GET /products/category/{categoryId}
```

#### 3. Produtos por Loja (público)
```http
GET /products/shop/{shopId}
```

### 💳 Pagamentos

#### 1. Listar Pagamentos
```http
GET /payments
Authorization: Bearer {token}
```

#### 2. Criar Pagamento
```http
POST /payments
Authorization: Bearer {token}
Content-Type: application/json

{
  "order_id": 1,
  "amount": 51.80,
  "payment_method": "pix",
  "transaction_id": "PIX123456789",
  "description": "Pagamento via PIX"
}
```

#### 3. Confirmar Pagamento
```http
POST /payments/{id}/confirm
Authorization: Bearer {token}
Content-Type: application/json

{
  "transaction_id": "PIX123456789"
}
```

#### 4. Pagamentos de um Pedido
```http
GET /payments/order/{orderId}
Authorization: Bearer {token}
```

## 🎭 Roles e Permissões

### Roles Disponíveis:
- **admin** (ID: 1) - Acesso total
- **manager** (ID: 2) - Gerenciamento
- **customer** (ID: 3) - Cliente
- **driver** (ID: 4) - Motorista
- **agent** (ID: 5) - Agente
- **shop_owner** (ID: 6) - Dono de loja

### Endpoints Específicos por Role:

#### Dashboard Admin/Manager
```http
GET /dashboard/stats
Authorization: Bearer {token}
```

#### Dashboard Motorista
```http
GET /driver/dashboard
Authorization: Bearer {token}
```

#### Pedidos do Cliente
```http
GET /customer/orders
Authorization: Bearer {token}
```

## 📊 Status dos Pedidos

1. **pending** - Pendente
2. **confirmed** - Confirmado
3. **assigned** - Designado
4. **collecting** - Coletando
5. **collected** - Coletado
6. **in_transit** - Em Trânsito
7. **delivering** - Entregando
8. **delivered** - Entregue
9. **cancelled** - Cancelado
10. **failed** - Falhou

## 🚗 Tipos de Veículos

1. **Moto**
2. **Carro**
3. **Van**
4. **Caminhão 3/4**
5. **Caminhão Toco**
6. **Caminhão Truck**
7. **Carreta**
8. **Bitrem**

## 💰 Métodos de Pagamento

- **cash** - Dinheiro
- **card** - Cartão
- **pix** - PIX
- **transfer** - Transferência
- **credit** - Crediário

## 📱 Headers Necessários

```http
Content-Type: application/json
Authorization: Bearer {token}
Accept: application/json
```

## 🔒 Códigos de Erro

- **401** - Não autenticado
- **403** - Sem permissão
- **404** - Não encontrado
- **422** - Erro de validação
- **500** - Erro interno

## 🧪 Dados de Teste

### Usuários Criados pelo Seeder:
- **Admin**: admin@kanguru.com / password
- **Motorista**: motorista@kanguru.com / password  
- **Cliente**: cliente@kanguru.com / password

### Lojas de Exemplo:
- **Restaurante Sabor & Arte**
- **Farmácia São João**
- **Supermercado Economia**

Esta API está pronta para ser consumida pelo seu app Flutter! 🚀
