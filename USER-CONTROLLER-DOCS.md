# 👥 UserController - Endpoints de Gestão de Usuários

## Base URL
```
http://127.0.0.1:8001/api/v1
```

### 🔐 Autenticação Necessária
Todos os endpoints requerem autenticação via Bearer Token e permissões de **admin** ou **manager**.

---

## 📋 Endpoints Disponíveis

### 1. Listar Todos os Usuários
```http
GET /users
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "is_active": true,
      "role": {
        "id": 3,
        "name": "customer",
        "display_name": "Cliente"
      },
      "profile": {
        "phone": "(11) 99999-9999",
        "document": "123.456.789-00",
        "address": "Rua A, 123"
      }
    }
  ]
}
```

---

### 2. Criar Novo Usuário
```http
POST /users
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Maria Santos",
  "email": "maria@example.com",
  "password": "12345678",
  "password_confirmation": "12345678",
  "role_id": 4,
  "phone": "(11) 88888-8888",
  "document": "987.654.321-00",
  "address": "Rua B, 456",
  "city": "São Paulo",
  "state": "SP",
  "postal_code": "01000-000"
}
```

---

### 3. Visualizar Usuário Específico
```http
GET /users/{id}
Authorization: Bearer {token}
```

---

### 4. Atualizar Usuário
```http
PUT /users/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "João Silva Santos",
  "email": "joao.santos@example.com",
  "role_id": 3,
  "is_active": true,
  "phone": "(11) 77777-7777",
  "birth_date": "1990-05-15",
  "address": "Rua C, 789",
  "city": "São Paulo",
  "state": "SP",
  "postal_code": "02000-000",
  "bio": "Cliente há 2 anos"
}
```

**Campos Opcionais:**
- `password` - Nova senha (com `password_confirmation`)
- `is_active` - Status ativo/inativo
- Todos os campos de perfil são opcionais

---

### 5. Desativar/Excluir Usuário
```http
DELETE /users/{id}
Authorization: Bearer {token}
```

**Comportamento:**
- ✅ **Soft Delete**: Marca como `is_active = false`
- ❌ **Impede exclusão** se usuário tiver:
  - Pedidos ativos em andamento
  - Veículos designados (motoristas)

**Response (Sucesso):**
```json
{
  "status": "success",
  "message": "User deactivated successfully"
}
```

**Response (Erro - Pedidos Ativos):**
```json
{
  "status": "error",
  "message": "Cannot delete user with active orders"
}
```

---

### 6. Ativar/Desativar Usuário
```http
PATCH /users/{id}/toggle-status
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": "success",
  "message": "User activated successfully",
  "data": {
    "id": 1,
    "name": "João Silva",
    "is_active": true,
    "role": {...},
    "profile": {...}
  }
}
```

---

### 7. Buscar Usuários por Role
```http
GET /users/role/{roleName}
Authorization: Bearer {token}
```

**Roles Disponíveis:**
- `admin`
- `manager`
- `customer`
- `driver`
- `agent`
- `shop_owner`

**Exemplo:**
```http
GET /users/role/driver
GET /users/role/customer
```

---

### 8. Listar Motoristas Disponíveis
```http
GET /drivers/available
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": "success",
  "message": "Available drivers retrieved successfully",
  "data": [
    {
      "id": 2,
      "name": "José Motorista",
      "email": "jose@example.com",
      "is_active": true,
      "role": {
        "name": "driver",
        "display_name": "Motorista"
      },
      "profile": {
        "phone": "(11) 99999-1111",
        "document": "111.222.333-44"
      },
      "vehicles": [
        {
          "id": 1,
          "license_plate_number": "ABC-1234",
          "model": "Honda CG 160",
          "vehicle_status": {
            "name": "available"
          }
        }
      ]
    }
  ]
}
```

---

## 🔒 Validações

### Criar/Atualizar Usuário:
- **name**: obrigatório, string, máx 255 chars
- **email**: obrigatório, email válido, único
- **password**: obrigatório na criação, mín 8 chars, com confirmação
- **role_id**: obrigatório, deve existir na tabela roles
- **phone**: opcional, string, máx 20 chars
- **document**: opcional, string, máx 20 chars
- **birth_date**: opcional, data válida
- **address**: opcional, string
- **city**: opcional, string, máx 100 chars
- **state**: opcional, string, máx 2 chars
- **postal_code**: opcional, string, máx 10 chars
- **bio**: opcional, string, máx 500 chars

---

## 📊 Casos de Uso

### 1. **Dashboard Admin**
```http
GET /users
GET /users/role/driver
GET /drivers/available
```

### 2. **Gestão de Motoristas**
```http
GET /users/role/driver
GET /drivers/available
PATCH /users/{id}/toggle-status
```

### 3. **Gestão de Clientes**
```http
GET /users/role/customer
PUT /users/{id}
```

### 4. **Onboarding**
```http
POST /users
PATCH /users/{id}/toggle-status
```

---

## ⚡ Exemplos de Requisições

### Criar Motorista:
```bash
curl -X POST http://127.0.0.1:8001/api/v1/users \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Carlos Motorista",
    "email": "carlos@kanguru.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "role_id": 4,
    "phone": "(11) 99999-2222",
    "document": "555.666.777-88"
  }'
```

### Buscar Motoristas:
```bash
curl -X GET http://127.0.0.1:8001/api/v1/drivers/available \
  -H "Authorization: Bearer {token}"
```

### Desativar Usuário:
```bash
curl -X PATCH http://127.0.0.1:8001/api/v1/users/5/toggle-status \
  -H "Authorization: Bearer {token}"
```

---

✅ **UserController completamente implementado com CRUD + métodos auxiliares!**