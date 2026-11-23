-- ============================================================
--  Echo — esquema para Supabase
--  Pégalo en Supabase → SQL Editor → New query → Run
-- ============================================================

-- TAREAS
create table if not exists public.tasks (
  id              uuid primary key default gen_random_uuid(),
  user_id         uuid not null default auth.uid() references auth.users(id) on delete cascade,
  title           text not null check (char_length(title) between 1 and 140),
  notes           text,
  due_date        date,
  priority        text not null default 'media' check (priority in ('baja','media','alta')),
  status          text not null default 'pendiente' check (status in ('pendiente','hecha','postergada')),
  postponed_count int  not null default 0,
  done_at         timestamptz,
  created_at      timestamptz not null default now()
);
create index if not exists tasks_user_idx on public.tasks(user_id, due_date);

-- MOVIMIENTOS FINANCIEROS
-- type: gasto | ingreso | ahorro (aporte al ahorro) | retiro (sacar del ahorro)
create table if not exists public.transactions (
  id          uuid primary key default gen_random_uuid(),
  user_id     uuid not null default auth.uid() references auth.users(id) on delete cascade,
  type        text not null check (type in ('gasto','ingreso','ahorro','retiro')),
  amount      numeric(12,2) not null check (amount > 0),
  category    text not null,
  note        text,
  date        date not null default current_date,
  created_at  timestamptz not null default now()
);
create index if not exists transactions_user_idx on public.transactions(user_id, date);

-- SEGURIDAD: cada usuario solo ve y modifica sus propios datos
alter table public.tasks        enable row level security;
alter table public.transactions enable row level security;

drop policy if exists "tasks_own" on public.tasks;
create policy "tasks_own" on public.tasks
  for all to authenticated using ((select auth.uid()) = user_id) with check ((select auth.uid()) = user_id);

drop policy if exists "transactions_own" on public.transactions;
create policy "transactions_own" on public.transactions
  for all to authenticated using ((select auth.uid()) = user_id) with check ((select auth.uid()) = user_id);
