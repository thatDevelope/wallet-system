<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Add this import at the top

class WalletController extends Controller{

    public function fundWallet(Request $request)
{
    // Validate the input
    $request->validate([
        'user_id' => ['required', 'exists:wallets,user_id'],
        'amount' => ['required', 'numeric', 'min:1'],
    ]);

    try {
        // Begin a database transaction
        DB::transaction(function () use ($request) {
            // Retrieve the user's wallet
            $wallet = DB::table('wallets')->where('user_id', $request->user_id)->first();

            if (!$wallet) {
                throw new \Exception("Wallet not found for the given user ID.");
            }

            // Update the wallet balance
            DB::table('wallets')
                ->where('user_id', $request->user_id)
                ->update([
                    'balance' => $wallet->balance + $request->amount, // Add to the current balance
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'message' => 'Wallet funded successfully!',
            'user_id' => $request->user_id,
            'amount' => $request->amount,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fund wallet: ' . $e->getMessage(),
        ], 500);
    }
}


public function transferFunds(Request $request)
{
    // Validate the input
    $request->validate([
        'sender_id' => ['required', 'exists:wallets,user_id'],
        'receiver_id' => ['required', 'exists:wallets,user_id', 'different:sender_id'],
        'amount' => ['required', 'numeric', 'min:1'],
    ]);

    try {
        // Begin a database transaction
        DB::transaction(function () use ($request) {
            // Retrieve sender's wallet
            $senderWallet = DB::table('wallets')->where('user_id', $request->sender_id)->first();

            // Check if sender has sufficient balance
            if ($senderWallet->balance < $request->amount) {
                throw new \Exception("Insufficient balance in sender's wallet.");
            }

            // Deduct amount from sender's wallet
            DB::table('wallets')
                ->where('user_id', $request->sender_id)
                ->update([
                    'balance' => $senderWallet->balance - $request->amount,
                    'updated_at' => now(),
                ]);

            // Retrieve receiver's wallet
            $receiverWallet = DB::table('wallets')->where('user_id', $request->receiver_id)->first();

            // Add amount to receiver's wallet
            DB::table('wallets')
                ->where('user_id', $request->receiver_id)
                ->update([
                    'balance' => $receiverWallet->balance + $request->amount,
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'message' => 'Transfer successful!',
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'amount' => $request->amount,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Transfer failed: ' . $e->getMessage(),
        ], 500);
    }
}


public function createWallet(Request $request)
{
    // Validate the input
    $request->validate([
        'user_id' => ['required', 'exists:users,id'],
        'name' => ['required', 'string', 'max:255'], // Unique wallet name for this user
    ]);

    try {
        // Check if the wallet name is already in use for this user
        $existingWallet = DB::table('wallets')
            ->where('user_id', $request->user_id)
            ->where('name', $request->name)
            ->first();

        if ($existingWallet) {
            return response()->json([
                'error' => 'You already have a wallet with this name.',
            ], 400);
        }

        // Create a new wallet
        DB::table('wallets')->insert([
            'user_id' => $request->user_id,
            'name' => $request->name, // Wallet name or label
            'balance' => 0, // Initial balance
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Wallet created successfully!',
            'user_id' => $request->user_id,
            'name' => $request->name,
            'balance' => 0,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to create wallet: ' . $e->getMessage(),
        ], 500);
    }
}


public function getAllUsers()
{
    try {
        // Retrieve all users from the 'users' table
        $users = DB::table('users')->get();

        // Return the list of users as JSON response
        return response()->json([
            'message' => 'All users retrieved successfully!',
            'data' => $users,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to retrieve users: ' . $e->getMessage(),
        ], 500);
    }
}

public function getAllWallets()
{
    try {
        // Retrieve all wallets from the 'wallets' table
        $wallets = DB::table('wallets')->get();

        // Return the list of wallets as JSON response
        return response()->json([
            'message' => 'All wallets retrieved successfully!',
            'data' => $wallets,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to retrieve wallets: ' . $e->getMessage(),
        ], 500);
    }
}

public function getWalletDetails($walletId)
{
    try {
        // Retrieve wallet details including owner (user) and balance
        $wallet = DB::table('wallets')
            ->join('users', 'wallets.user_id', '=', 'users.id') // Join users table
            ->select(
                'wallets.id as wallet_id',
                'wallets.name as wallet_name',
                'wallets.balance as available_balance',
                'users.id as owner_id',
                'users.name as owner_name',
                'users.email as owner_email',
                'wallets.created_at',
                'wallets.updated_at'
            )
            ->where('wallets.id', $walletId) // Filter by wallet ID
            ->first();

        if (!$wallet) {
            return response()->json([
                'error' => 'Wallet not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Wallet details retrieved successfully!',
            'data' => $wallet,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to retrieve wallet details: ' . $e->getMessage(),
        ], 500);
    }
}
}