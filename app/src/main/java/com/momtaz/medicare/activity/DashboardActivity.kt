package com.momtaz.medicare.activity

import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import androidx.navigation.findNavController
import androidx.navigation.ui.setupWithNavController
import com.momtaz.medicare.R
import com.momtaz.medicare.databinding.ActivityDashboardBinding


class DashboardActivity : AppCompatActivity() {
    private lateinit var binding: ActivityDashboardBinding
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)
        binding.fab.setOnClickListener {
           findNavController(R.id.fragment_host_dashboard).navigate(R.id.chatbotFragment2)
        }

    }

    override fun onResume() {
        super.onResume()
        val navController =findNavController(R.id.fragment_host_dashboard)
        binding.bottomNavigationView.setupWithNavController(navController)
    }
}